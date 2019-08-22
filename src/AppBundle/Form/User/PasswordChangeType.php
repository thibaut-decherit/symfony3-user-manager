<?php

namespace AppBundle\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * Class PasswordChangeType
 * @package AppBundle\Form\User
 */
class PasswordChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'user.current_password',
                'mapped' => false,
                'required' => false,
                'constraints' => array(
                    new UserPassword([
                        'message' => 'form_errors.user.wrong_password',
                        'groups' => ['Password_Change']
                    ]),
                ),
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'form_errors.user.repeat_password',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => false,
                'first_options' => array('label' => 'user.new_password'),
                'second_options' => array('label' => 'user.new_password_repeat'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User',
            'validation_groups' => array('Password_Change')
        ));
    }

    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }
}
