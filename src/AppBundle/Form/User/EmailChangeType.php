<?php

namespace AppBundle\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserInformationType
 * @package AppBundle\Form\User
 */
class EmailChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('emailChangePending', EmailType::class, [
                'label' => 'user.email_address',
                'data' => '',
                'required' => false,
                'attr' => [
                    'placeholder' => $builder->getData()->getEmail(),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User',
            'validation_groups' => array('Email_Change')
        ));
    }

    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }
}
