<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\ClientModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', TextType::class, [
                'label' => 'client.form.name.label',
                'attr' => [
                    'placeholder' => 'client.form.name.placeholder',
                    'class' => 'form-control',
                ],
                'help' => 'client.form.name.help',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'client.form.submit.label',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClientModel::class,
        ]);
    }
}
