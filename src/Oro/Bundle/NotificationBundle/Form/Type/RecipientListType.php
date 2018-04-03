<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Oro\Bundle\UserBundle\Form\Type\OrganizationUserAclMultiSelectType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
            OrganizationUserAclMultiSelectType::class,
            [
                'required' => false,
                'label'    => 'oro.user.entity_plural_label'
            ]
        );

        // groups
        $builder->add(
            'groups',
            EntityType::class,
            [
                'label'       => 'oro.user.group.entity_plural_label',
                'class'       => 'OroUserBundle:Group',
                'choice_label'      => 'name',
                'multiple'      => true,
                'expanded'      => true,
                'placeholder'   => '',
                'empty_data'    => null,
                'required'      => false,
            ]
        );

        // custom email
        $builder->add(
            'email',
            EmailType::class,
            ['label' => 'oro.notification.emailnotification.email.label', 'required' => false]
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
                'csrf_token_id' => 'recipientlist',
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
