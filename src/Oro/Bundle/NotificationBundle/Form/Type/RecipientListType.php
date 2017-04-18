<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipientListType extends AbstractType
{
    const NAME = 'oro_notification_recipient_list';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'users',
            'oro_user_organization_acl_multiselect',
            [
                'required' => false,
                'label'    => 'oro.user.entity_plural_label'
            ]
        );

        // groups
        $builder->add(
            'groups',
            'entity',
            [
                'label'       => 'oro.user.group.entity_plural_label',
                'class'       => 'OroUserBundle:Group',
                'property'      => 'name',
                'multiple'      => true,
                'expanded'      => true,
                'empty_value'   => '',
                'empty_data'    => null,
                'required'      => false,
            ]
        );

        // custom email
        $builder->add(
            'email',
            'email',
            ['label' => 'oro.notification.emailnotification.email.label', 'required' => false]
        );

        // owner
        $builder->add(
            'owner',
            'checkbox',
            ['label' => 'oro.notification.emailnotification.owner.label', 'required' => false]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\NotificationBundle\Entity\RecipientList',
                'intention' => 'recipientlist',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
