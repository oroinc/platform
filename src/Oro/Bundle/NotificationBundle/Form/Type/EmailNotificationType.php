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
    protected $entityNameChoices = array();

    /**
     * @var array
     */
    protected $entitiesData = array();

    /**
     * @var BuildTemplateFormSubscriber
     */
    protected $subscriber;

    /**
     * @param array                       $entitiesConfig
     * @param BuildTemplateFormSubscriber $subscriber
     * @param ConfigProvider              $configManager
     */
    public function __construct($entitiesConfig, BuildTemplateFormSubscriber $subscriber, ConfigProvider $configManager)
    {
        $this->subscriber = $subscriber;
        $this->entityNameChoices = array_map(
            function ($value) {
                return isset($value['name']) ? $value['name'] : '';
            },
            $entitiesConfig
        );

        $this->entitiesData = $entitiesConfig;
        array_walk(
            $this->entitiesData,
            function (&$value, $class) use ($configManager) {
                $ownerType = $configManager->hasConfig($class) ?
                    $configManager->getConfig($class)->get('owner_type') : null;
                $value = !empty($ownerType) && $ownerType != OwnershipType::OWNER_TYPE_NONE;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);

        $builder->add(
            'entityName',
            'choice',
            array(
                'choices'            => $this->entityNameChoices,
                'multiple'           => false,
                'translation_domain' => 'config',
                'empty_value'        => '',
                'empty_data'         => null,
                'required'           => true,
                'attr'               => array(
                    'data-entities' => json_encode($this->entitiesData)
                )
            )
        );

        $builder->add(
            'event',
            'entity',
            array(
                'class'         => 'OroNotificationBundle:Event',
                'property'      => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'empty_value'   => '',
                'empty_data'    => null,
                'required'      => true
            )
        );

        $builder->add(
            'template',
            'oro_email_template_list',
            array(
                'required' => true
            )
        );

        $builder->add(
            'recipientList',
            'oro_notification_recipient_list',
            array(
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
