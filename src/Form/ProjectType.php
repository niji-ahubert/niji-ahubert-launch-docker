<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Environment;
use App\Form\Model\ProjectModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', TextType::class, [
                'label' => 'form.client',
                'required' => true,
                'label_attr' => [
                    'class' => 'form-label fw-bold',
                ],
                'attr' => [
                    'readonly' => true,
                    'class' => 'bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500',
                ],
            ])
            ->add('project', TextType::class, [
                'label' => 'form.project',
                'required' => true,
            ])
            ->add('traefikNetwork', TextType::class, [
                'label' => 'form.traefik_network',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('environmentContainer', EnumType::class, [
                'class' => Environment::class,
                'label' => 'form.environment',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.save',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectModel::class,
        ]);
    }
}