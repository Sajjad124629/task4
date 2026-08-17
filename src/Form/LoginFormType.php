<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control', 'id' => 'exampleInputEmail1', 'aria-describedby' => 'emailHelp', 'autofocus' => true],
                'label_attr' => ['class' => 'form-label']
               
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'attr' => ['class' => 'form-control', 'id' => 'exampleInputPassword1'],
                'label_attr' => ['class' => 'form-label']
            ])
            ->add('remember_me', CheckboxType::class, [
                'label' => 'Remember this Device',
                'required' => false,
                'attr' => ['class' => 'form-check-input primary', 'id' => 'flexCheckChecked'],
                'label_attr' => ['class' => 'form-check-label text-dark fs-3']
            ])
           
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id'   => 'authenticate',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
