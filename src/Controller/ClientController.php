<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ClientType;
use App\Form\Model\ClientModel;
use App\Normalizer\ClientNameNormalizer;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

class ClientController extends AbstractController
{
    public function __construct(
        private readonly FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private readonly TranslatorInterface $translator,
        private readonly ClientNameNormalizer $clientNameNormalizer,
    ) {
    }

    #[Route('/client/', name: 'app_client_list')]
    public function list(): Response
    {
        $clients = $this->fileSystemEnvironmentServices->getFolder(FileSystemEnvironmentServices::PROJECT_ROOT_FOLDER_IN_DOCKER);

        return $this->render('client/list.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/client/add', name: 'app_client_add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $clientModel = new ClientModel();
        $form = $this->createForm(ClientType::class, $clientModel);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Normaliser le nom du client pour l'utilisation comme nom de dossier
            $clientName = $this->clientNameNormalizer->normalize($clientModel->getClient());

            $this->fileSystemEnvironmentServices->createClientFolder($clientName);

            // Message de succès avec le nom normalisé
            $this->addFlash('success', $this->translator->trans('client.created.success', ['%client%' => $clientName]));

            // Redirection vers la liste des clients
            return $this->redirectToRoute('app_client_list');
        }

        return $this->render('client/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/client/edit/{clientName}', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(string $clientName, Request $request): Response
    {
        // Vérifier que le client existe
        if (!$this->fileSystemEnvironmentServices->clientFolderExists($clientName)) {
            $this->addFlash('error', $this->translator->trans('client.not_found', ['%client%' => $clientName]));

            return $this->redirectToRoute('app_client_list');
        }

        // Créer le modèle avec le nom original
        $clientModel = new ClientModel($clientName);
        $form = $this->createForm(ClientType::class, $clientModel);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $newClientName = $this->clientNameNormalizer->normalize($clientModel->getClient());

                // Si le nom a changé, renommer le dossier
                if ($clientName !== $newClientName) {
                    $this->fileSystemEnvironmentServices->renameClientFolder($clientName, $newClientName);
                    $this->fileSystemEnvironmentServices->updateClientNameInSocleFiles($clientName, $newClientName);
                    $this->addFlash('success', $this->translator->trans('client.update.success', [
                        '{{ client_name }}' => $newClientName,
                    ]));
                } else {
                    $this->addFlash('success', $this->translator->trans('client.updated.no_change', ['%client%' => $clientName]));
                }

                return $this->redirectToRoute('app_client_list');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $this->translator->trans('client.update.error', ['%error%' => $e->getMessage()]));
            }
        }

        return $this->render('client/edit.html.twig', [
            'form' => $form->createView(),
            'client_name' => $clientName,
        ]);
    }

    #[Route('/client/delete/{clientName}', name: 'app_client_delete', methods: ['POST'])]
    public function delete(string $clientName, Request $request): Response
    {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        Assert::string($token);
        if (!$this->isCsrfTokenValid('delete_client_'.$clientName, $token)) {
            $this->addFlash('error', $this->translator->trans('security.csrf.invalid'));

            return $this->redirectToRoute('app_client_list');
        }

        // Vérifier que le client existe
        if (!$this->fileSystemEnvironmentServices->clientFolderExists($clientName)) {
            $this->addFlash('error', $this->translator->trans('client.not_found', ['%client%' => $clientName]));

            return $this->redirectToRoute('app_client_list');
        }

        try {
            $this->fileSystemEnvironmentServices->deleteClientFolder($clientName);
            $this->addFlash('success', $this->translator->trans('client.delete.success', ['{{ client_name }}' => $clientName]));
        } catch (\RuntimeException) {
            $this->addFlash('error', $this->translator->trans('client.delete.error'));
        }

        return $this->redirectToRoute('app_client_list');
    }

    #[Route('/client/check-availability', name: 'app_client_check_availability', methods: ['GET'])]
    public function checkAvailability(Request $request): JsonResponse
    {
        $clientName = $request->query->get('name', '');

        if (empty($clientName)) {
            return new JsonResponse(['available' => false, 'message' => 'Nom vide']);
        }

        try {
            $normalizedName = $this->clientNameNormalizer->normalize($clientName);
            $existingClients = $this->fileSystemEnvironmentServices->getFolder(FileSystemEnvironmentServices::PROJECT_ROOT_FOLDER_IN_DOCKER);
            $exists = array_any($existingClients, fn ($existingClient): bool => strtolower((string) $existingClient) === strtolower($normalizedName));

            if ($exists) {
                return new JsonResponse([
                    'available' => false,
                    'message' => $this->translator->trans('validator.client.name_already_exists', [
                        '{{ client_name }}' => $normalizedName,
                    ]),
                    'normalized' => $normalizedName,
                ]);
            }

            return new JsonResponse([
                'available' => true,
                'message' => $this->translator->trans('client.created.name.available', [
                    '{{ client_name }}' => $normalizedName,
                ]),
                'normalized' => $normalizedName,
            ]);
        } catch (\Exception) {
            return new JsonResponse([
                'available' => false,
                'message' => $this->translator->trans('validator.client.name_filesystem_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
