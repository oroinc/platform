<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;

class EmailNotificationType extends AbstractType
{
    /**
     * @var array
     */
    protected $ownershipEntities = array();

    /**
     * @var BuildTemplateFormSubscriber
     */
    protected $subscriber;

    /**
     * @param BuildTemplateFormSubscriber $subscriber
     * @param ConfigProvider              $ownershipConfigProvider
     */
    public function __construct(
        BuildTemplateFormSubscriber $subscriber,
        ConfigProvider $ownershipConfigProvider
    ) {
        $this->subscriber = $subscriber;

        $this->ownershipEntities = [];
        foreach ($ownershipConfigProvider->getConfigs() as $config) {
            $ownerType = $config->get('owner_type');
            if (!empty($ownerType) && $ownerType != OwnershipType::OWNER_TYPE_NONE) {
                $this->ownershipEntities[$config->getId()->getClassName()] = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);

        $builder->add(
            'entityName',
            'oro_email_notification_entity_choice',
            array(
                'label'              => 'oro.notification.emailnotification.entity_name.label',
                'required'           => true,
                'attr'               => array(
                    'data-ownership-entities' => json_encode($this->ownershipEntities)
                )
            )
        );

        $builder->add(
            'event',
            'genemu_jqueryselect2_entity',
            array(
                'label'         => 'oro.notification.emailnotification.event.label',
                'class'         => 'OroNotificationBundle:Event',
                'property'      => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'configs' => array(
                    'placeholder' => 'oro.notification.form.choose_event',
                ),
                'empty_value'   => '',
                'empty_data'    => null,
                'required'      => true
            )
        );

        $builder->add(
            'template',
            'oro_email_template_list',
            array(
                'label'    => 'oro.notification.emailnotification.template.label',
                'required' => true
            )
        );

        $builder->add(
            'recipientList',
            'oro_notification_recipient_list',
            array(
                'label'    => 'oro.notification.emailnotification.recipient_list.label',
                'required' => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'           => 'Oro\Bundle\NotificationBundle\Entity\EmailNotification',
                'intention'            => 'emailnotification',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'cascade_validation'   => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emailnotification';
    }
}
