<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;

class EmailNotificationType extends AbstractType
{
    /**
     * @var array
     */
    protected $ownershipEntities = [];

    /**
     * @var BuildTemplateFormSubscriber
     */
    protected $buildTemplateSubscriber;

    /**
     * @var AdditionalEmailsSubscriber
     */
    protected $additionalEmailsSubscriber;

    /** @var RouterInterface */
    private $router;

    /**
     * @param BuildTemplateFormSubscriber $buildTemplateSubscriber
     * @param AdditionalEmailsSubscriber $additionalEmailsSubscriber
     * @param ConfigProvider $ownershipConfigProvider
     * @param RouterInterface $router
     */
    public function __construct(
        BuildTemplateFormSubscriber $buildTemplateSubscriber,
        AdditionalEmailsSubscriber $additionalEmailsSubscriber,
        ConfigProvider $ownershipConfigProvider,
        RouterInterface $router
    ) {
        $this->buildTemplateSubscriber = $buildTemplateSubscriber;
        $this->additionalEmailsSubscriber = $additionalEmailsSubscriber;
        $this->router = $router;

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
        $builder->addEventSubscriber($this->buildTemplateSubscriber);
        $builder->addEventSubscriber($this->additionalEmailsSubscriber);

        $builder->add(
            'entityName',
            'oro_email_notification_entity_choice',
            [
                'label'       => 'oro.notification.emailnotification.entity_name.label',
                'tooltip'     => 'oro.notification.emailnotification.entity_name.tooltip',
                'required'    => true,
                'attr'        => [
                    'data-ownership-entities' => json_encode($this->ownershipEntities)
                ],
                'configs'     => [
                    'allowClear' => true
                ],
                'tooltip_parameters' => [
                    'url' => $this->router->generate('oro_email_emailtemplate_index')
                ],
            ]
        );

        $builder->add(
            'event',
            'genemu_jqueryselect2_entity',
            [
                'label'         => 'oro.notification.emailnotification.event.label',
                'class'         => 'OroNotificationBundle:Event',
                'property'      => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'configs'       => [
                    'allowClear'  => true,
                    'placeholder' => 'oro.notification.form.choose_event',
                ],
                'empty_value'   => '',
                'required'      => true
            ]
        );

        $builder->add(
            'template',
            'oro_email_template_list',
            [
                'label'       => 'oro.notification.emailnotification.template.label',
                'required'    => true,
                'configs'     => [
                    'allowClear' => true
                ],
            ]
        );

        $builder->add(
            'recipientList',
            'oro_notification_recipient_list',
            [
                'label'    => 'oro.notification.emailnotification.recipient_list.label',
                'required' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\NotificationBundle\Entity\EmailNotification',
                'intention'            => 'emailnotification',
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
        return 'emailnotification';
    }
}
