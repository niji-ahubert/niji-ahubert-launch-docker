<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\ContainerType\ServiceContainer;
use App\Form\Model\ServiceExternalModel;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\StrategyManager\ContainerServices;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class ServiceExternalType extends AbstractType
{
    public function __construct(
        private readonly ContainerServices $containerServices,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('serviceName', ChoiceType::class, [
                'label' => 'form.service_external.service_name',
                'required' => true,
                'choices' => $this->getServiceChoices(),
                'placeholder' => 'form.service_external.service_name_placeholder',
                'choice_attr' => fn($choice, $key, $value): array => // Optional: add data attributes for JavaScript use
                ['data-service' => $value],
                'disabled' => $options['is_edit_mode'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'envoyer',
            ]);

        // Add an event listener to dynamically add the version field based on the selected service
        $formModifier = function (FormInterface $form, ?string $serviceName = null): void {
            if ($serviceName) {
                $serviceContainer = $this->containerServices->getServiceContainer($serviceName);
                $versionChoices = $serviceContainer instanceof AbstractContainer ? $this->getVersionChoicesForService($serviceContainer) : [];

                $form->add('version', ChoiceType::class, [
                    'label' => 'form.service_external.version',
                    'required' => true,
                    'choices' => $versionChoices,
                    'placeholder' => 'form.service_external.version_placeholder',
                    'attr' => ['class' => 'version-select'],
                ]);
            } else {
                $form->add('version', ChoiceType::class, [
                    'label' => 'form.service_external.version',
                    'required' => true,
                    'choices' => [],
                    'placeholder' => 'Veuillez d\'abord sélectionner un service',
                    'disabled' => true,
                    'attr' => ['class' => 'version-select'],
                ]);
            }
        };

        // Instead of calling $formModifier directly with $builder,

        // When the serviceName field is updated, update the version field
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier): void {
                /** @var ServiceExternalModel $data */
                $data = $event->getData();
                $serviceName = $data->getServiceName();
                Assert::nullOrString($serviceName);
                Assert::isInstanceOf($event->getForm(), FormInterface::class);
                $formModifier($event->getForm(), $serviceName);
            },
        );

        $builder->get('serviceName')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier): void {
                $serviceName = $event->getForm()->getData();
                Assert::nullOrString($serviceName);
                Assert::isInstanceOf($event->getForm()->getParent(), FormInterface::class);
                $formModifier($event->getForm()->getParent(), $serviceName);
            },
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['is_edit_mode'] = $options['is_edit_mode'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceExternalModel::class,
            'is_edit_mode' => false,
        ]);

        $resolver->setRequired('project');
        $resolver->setAllowedTypes('project', Project::class);
    }

    /**
     * @return array<string,string>
     */
    private function getServiceChoices(): array
    {
        $choices = [];
        foreach (ServiceContainer::cases() as $service) {
            $choices[$service->value] = $service->value;
        }

        return $choices;
    }

    /**
     * @return array<string,string>
     */
    private function getVersionChoicesForService(AbstractContainer $serviceContainer): array
    {
        $versionChoices = [];
        $versions = $serviceContainer->getVersionSupported() ?? [];

        foreach ($versions as $version) {
            $versionChoices[$version] = $version;
        }

        return $versionChoices;
    }
}
