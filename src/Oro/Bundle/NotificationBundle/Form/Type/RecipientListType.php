<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipientListType extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'users',
            'oro_user_multiselect',
            array(
                'required' => false
            )
        );

        // groups
        $builder->add(
            'groups',
            'entity',
            array(
                'class'         => 'OroUserBundle:Group',
                'property'      => 'name',
                'multiple'      => true,
                'expanded'      => true,
                'empty_value'   => '',
                'empty_data'    => null,
                'required'      => false,
            )
        );

        // custom email
        $builder->add(
            'email',
            EmailType::class,
            array(
                'required'      => false
            )
        );

        // owner
        $builder->add(
            'owner',
            CheckboxType::class,
            array(
                'required'      => false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'           => 'Oro\Bundle\NotificationBundle\Entity\RecipientList',
                'intention'            => 'recipientlist',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'cascade_validation'    => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_notification_recipient_list';
    }
}
