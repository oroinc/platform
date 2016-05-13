<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormSubscriber as IntegrationFormSubscriber;
use Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultOwnerSubscriber;

class ChannelType extends AbstractType
{
    const NAME            = 'oro_integration_channel_form';
    const TYPE_FIELD_NAME = 'type';

    /** @var DefaultOwnerSubscriber */
    protected $defaultOwnerSubscriber;

    /** @var IntegrationFormSubscriber */
    protected $integrationFormSubscriber;

    /**
     * @param DefaultOwnerSubscriber    $defaultOwnerSubscriber
     * @param IntegrationFormSubscriber $integrationFormSubscriber
     */
    public function __construct(
        DefaultOwnerSubscriber $defaultOwnerSubscriber,
        IntegrationFormSubscriber $integrationFormSubscriber
    ) {
        $this->defaultOwnerSubscriber = $defaultOwnerSubscriber;
        $this->integrationFormSubscriber  = $integrationFormSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->integrationFormSubscriber);
        $builder->addEventSubscriber($this->defaultOwnerSubscriber);

        $builder->add(
            self::TYPE_FIELD_NAME,
            'oro_integration_type_select',
            [
                'required' => true,
                'label'    => 'oro.integration.integration.type.label'
            ]
        );
        $builder->add('name', 'text', ['required' => true, 'label' => 'oro.integration.integration.name.label']);

        $builder->add(
            'enabled',
            'choice',
            [
                'choices'  => [
                    true    => 'oro.integration.integration.enabled.active.label',
                    false   => 'oro.integration.integration.enabled.inactive.label'
                ],
                'required' => true,
                'label'    => 'oro.integration.integration.enabled.label',
            ]
        );

        // add transport type selector
        $builder->add(
            'transportType',
            'choice',
            [
                'label'       => 'oro.integration.integration.transport.label',
                'choices'     => [], //will be filled in event listener
                'mapped'      => false,
                'constraints' => new NotBlank()
            ]
        );

        // add connectors
        $builder->add(
            'connectors',
            'choice',
            [
                'label'    => 'oro.integration.integration.connectors.label',
                'expanded' => true,
                'multiple' => true,
                'choices'  => [], //will be filled in event listener
                'required' => false,
            ]
        );

        // add default owner
        $builder->add(
            'defaultUserOwner',
            'oro_user_organization_acl_select',
            [
                'required' => true,
                'label'    => 'oro.integration.integration.default_user_owner.label',
                'tooltip'  => 'oro.integration.integration.default_user_owner.description',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\\Bundle\\IntegrationBundle\\Entity\\Channel',
                'intention'          => 'channel',
                'cascade_validation' => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
