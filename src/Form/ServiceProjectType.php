<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\DataStorage;
use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\PhpExtension;
use App\Enum\ServiceVersion\VersionLaravelSupported;
use App\Enum\ServiceVersion\VersionNodeSupported;
use App\Enum\ServiceVersion\VersionPhpSupported;
use App\Enum\ServiceVersion\VersionSymfonySupported;
use App\Enum\WebServer;
use App\Form\Model\ServiceProjectModel;
use App\Form\Service\AvailableServicesProvider;
use App\Model\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class ServiceProjectType extends AbstractType
{
    public function __construct(
        private readonly AvailableServicesProvider $availableServicesProvider,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('githubRepository', TextType::class, [
                'required' => false,
                'label' => 'form.github_repository',
                'help' => 'form.github_repository_help',
                'attr' => [
                    'placeholder' => 'git@github.com:organisation/repo.git',
                ],
            ])
            ->add('githubBranch', TextType::class, [
                'required' => false,
                'label' => 'form.github_branch',
                'help' => 'form.github_branch_help',
                'attr' => [
                    'placeholder' => 'main',
                ],
            ])
            ->add('folderName', TextType::class, [
                'required' => true,
                'label' => 'form.folder_name',
                'attr' => [
                    'class' => 'folder-name-input',
                ],
            ])
            ->add('language', EnumType::class, [
                'class' => ProjectContainer::class,
                'required' => true,
                'label' => 'form.language',
                'placeholder' => 'form.select_language',
                'attr' => [
                    'class' => 'language-select',
                ],
            ])
            ->add('urlService', TextType::class, [
                'required' => true,
                'label' => 'form.url_service',
                'help' => 'form.url_service_help',
                'attr' => [
                    'readonly' => true,
                    'class' => 'url-service-input',
                    'placeholder' => 'client.project.folder-name.docker.localhost',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'envoyer',
            ]);

        $project = $options['project'];
        /** @var Project $project */
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($project): void {
            $data = $event->getData();
            if (!$data instanceof ServiceProjectModel) {
                return;
            }

            $language = $data->getLanguage();
            $framework = $data->getFramework();
            $webserver = $data->getWebServer();
            $this->addDynamicFields($event->getForm(), $language, $framework, $project);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($project): void {
            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }

            $languageValue = $data['language'] ?? null;
            $frameworkValue = $data['framework'] ?? null;
            $webserverValue = $data['webserver'] ?? null;
            $language = $languageValue ? ProjectContainer::tryFrom((string) $languageValue) : null;
            $framework = $frameworkValue ? $this->getFrameworkEnum($language, $frameworkValue) : null;
            $webserver = $webserverValue ? WebServer::tryFrom((string) $webserverValue) : null;

            $this->addDynamicFields($event->getForm(), $language, $framework, $project);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceProjectModel::class,
            'is_edit_mode' => false,
        ]);

        $resolver->setRequired('project');
        $resolver->setAllowedTypes('project', Project::class);
    }

    /**
     * @param FormInterface<ServiceProjectModel>                                          $form
     * @param FrameworkLanguageInterface<FrameworkLanguageNode|FrameworkLanguagePhp>|null $framework
     */
    private function addDynamicFields(
        FormInterface $form,
        ?ProjectContainer $language,
        ?FrameworkLanguageInterface $framework,
        Project $project,
    ): void {
        $this->addVersionServiceField($form, $language);
        $this->addFrameworkField($form, $language);
        $this->addVersionFrameworkField($form, $framework);
        $this->addExtensionsField($form, $language);
        $this->addWebServerField($form);
        $this->addDataStorageField($form, $project);
    }

    /**
     * @param FormInterface<ServiceProjectModel> $form
     */
    private function addVersionServiceField(FormInterface $form, ?ProjectContainer $language): void
    {
        $versionClass = match ($language) {
            ProjectContainer::PHP => VersionPhpSupported::class,
            ProjectContainer::NODE => VersionNodeSupported::class,
            default => null,
        };

        $form->add('versionService', EnumType::class, [
            'required' => true,
            'label' => 'form.version_service',
            'placeholder' => 'form.select_language_first',
            'class' => $versionClass ?? VersionPhpSupported::class,
            'attr' => [
                'class' => 'version-service-select',
            ],
        ]);
    }

    /**
     * @param FormInterface<ServiceProjectModel> $form
     */
    private function addFrameworkField(FormInterface $form, ?ProjectContainer $language): void
    {
        $frameworkClass = match ($language) {
            ProjectContainer::NODE => FrameworkLanguageNode::class,
            default => FrameworkLanguagePhp::class,
        };

        $form->add('framework', EnumType::class, [
            'required' => false,
            'label' => 'form.framework',
            'placeholder' => 'form.select_language_first',
            'class' => $frameworkClass,
            'attr' => [
                'class' => 'framework-select',
            ],
        ]);
    }

    /**
     * @param FormInterface<ServiceProjectModel>                                          $form
     * @param FrameworkLanguageInterface<FrameworkLanguageNode|FrameworkLanguagePhp>|null $framework
     */
    private function addVersionFrameworkField(FormInterface $form, ?FrameworkLanguageInterface $framework): void
    {
        $versionFrameworkClass = $framework instanceof FrameworkLanguageInterface ? match ($framework->getValue()) {
            'symfony' => VersionSymfonySupported::class,
            'laravel' => VersionLaravelSupported::class,
            default => null,
        } : null;

        $versionFrameworkClass ??= VersionSymfonySupported::class;

        $form->add('versionFramework', EnumType::class, [
            'required' => false,
            'label' => 'form.version_framework',
            'placeholder' => 'form.select_framework_first',
            'class' => $versionFrameworkClass,
            'attr' => [
                'class' => 'version-framework-select',
            ],
        ]);
    }

    /**
     * @param FormInterface<ServiceProjectModel> $form
     */
    private function addExtensionsField(FormInterface $form, ?ProjectContainer $language): void
    {
        $extensionsChoices = [];

        if (ProjectContainer::PHP === $language) {
            $extensionsChoices = array_combine(
                array_map(static fn (PhpExtension $case): string => $case->value, PhpExtension::cases()),
                array_map(static fn (PhpExtension $case): string => $case->value, PhpExtension::cases()),
            );
        }

        $form->add('extensionsRequired', ChoiceType::class, [
            'required' => false,
            'label' => 'form.required_extensions',
            'multiple' => true,
            'expanded' => false,
            'attr' => [
                'class' => 'extensions-select',
                'data-placeholder' => 'form.select_extensions',
            ],
            'choices' => $extensionsChoices,
        ]);
    }

    /**
     * @param FormInterface<ServiceProjectModel> $form
     */
    private function addWebServerField(FormInterface $form): void
    {
        // Par défaut, on propose seulement LOCAL
        // Le JavaScript se chargera de charger les webservers disponibles selon le projet
        $form->add('webServer', EnumType::class, [
            'class' => WebServer::class,
            'required' => true,
            'label' => 'form.webserver',
            'placeholder' => 'form.select_webserver',
            'attr' => [
                'class' => 'webserver-select',
            ],
        ]);
    }

    /**
     * @param FormInterface<ServiceProjectModel> $form
     */
    private function addDataStorageField(FormInterface $form, Project $project): void
    {
        $availableStorages = $this->availableServicesProvider->getAvailableDataStorages($project);

        $form->add('dataStorages', EnumType::class, [
            'class' => DataStorage::class,
            'required' => false,
            'label' => 'form.data_storages',
            'multiple' => true,
            'expanded' => true,
            'choices' => $availableStorages,
            'choice_label' => fn (DataStorage $choice): string => $choice->trans($this->translator),
            'row_attr' => [
                'class' => 'data-storages-checkboxes',
            ],
            'attr' => [
                'data-placeholder' => 'form.data_storages',
            ],
        ]);
    }

    /**
     * @phpstan-return FrameworkLanguagePhp|FrameworkLanguageNode|null
     */
    private function getFrameworkEnum(?ProjectContainer $language, string $frameworkValue): ?FrameworkLanguageInterface
    {
        return match ($language) {
            ProjectContainer::PHP => FrameworkLanguagePhp::tryFrom($frameworkValue),
            ProjectContainer::NODE => FrameworkLanguageNode::tryFrom($frameworkValue),
            default => null,
        };
    }
}
