<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class EmailNotificationType extends AbstractType
{
    const NAME = 'emailnotification';

    /** @var BuildTemplateFormSubscriber */
    protected $subscriber;

    /** @var ConfigProvider */
    protected $ownershipConfigProvider;

    /** @var RouterInterface */
    private $router;

    /**
     * @param BuildTemplateFormSubscriber $subscriber
     * @param ConfigProvider $ownershipConfigProvider
     * @param RouterInterface $router
     */
    public function __construct(
        BuildTemplateFormSubscriber $subscriber,
        ConfigProvider $ownershipConfigProvider,
        RouterInterface $router
    ) {
        $this->subscriber = $subscriber;
        $this->ownershipConfigProvider = $ownershipConfigProvider;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);

        $builder->add(
            'entityName',
            EmailNotificationEntityChoiceType::NAME,
            [
                'label'       => 'oro.notification.emailnotification.entity_name.label',
                'tooltip'     => 'oro.notification.emailnotification.entity_name.tooltip',
                'required'    => true,
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
                    return $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');
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
            'genemu_jqueryselect2_translatable_entity',
            [
                'label' => 'oro.notification.emailnotification.template.label',
                'class' => 'OroEmailBundle:EmailTemplate',
                'configs' => [
                    'allowClear' => true,
                    'placeholder' => 'oro.email.form.choose_template',
                ],
                'empty_value' => '',
                'required' => true
            ]
        );

        $builder->add(
            'recipientList',
            RecipientListType::NAME,
            [
                'label'    => 'oro.notification.emailnotification.recipient_list.label',
                'required' => true,
            ]
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data instanceof EmailNotification) {
                    $entityName = $data->getEntityName();
                    $entities = $this->getOwnershipEntities();

                    if ($entityName && !array_key_exists($entityName, $entities)) {
                        $recipientList = $event->getForm()->get('recipientList');

                        FormUtils::replaceField(
                            $recipientList,
                            'owner',
                            array_merge($recipientList->get('owner')->getConfig()->getOptions(), ['disabled' => true])
                        );

                    }
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => EmailNotification::class,
                'intention' => self::NAME
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['listenChangeElements'] = array_filter(
            array_map(
                function (FormView $view) {
                    if ($view->vars['name'] === 'entityName') {
                        return '#' . $view->vars['id'];
                    }

                    return null;
                },
                array_values($view->children)
            )
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

    /**
     * @return array
     */
    private function getOwnershipEntities()
    {
        $ownershipEntities = [];
        foreach ($this->ownershipConfigProvider->getConfigs() as $config) {
            $ownerType = $config->get('owner_type');
            if (!empty($ownerType) && $ownerType !== OwnershipType::OWNER_TYPE_NONE) {
                $ownershipEntities[$config->getId()->getClassName()] = true;
            }
        }

        return $ownershipEntities;
    }
}
