<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormSubscriber as IntegrationFormSubscriber;
use Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultUserOwnerSubscriber;
use Oro\Bundle\IntegrationBundle\Form\EventListener\OrganizationSubscriber;

class ChannelType extends AbstractType
{
    const NAME            = 'oro_integration_channel_form';
    const TYPE_FIELD_NAME = 'type';

    /** @var DefaultUserOwnerSubscriber */
    protected $defaultUserOwnerSubscriber;

    /** @var IntegrationFormSubscriber */
    protected $integrationFormSubscriber;

    /**
     * @param DefaultUserOwnerSubscriber $defaultUserOwnerSubscriber
     * @param IntegrationFormSubscriber  $integrationFormSubscriber
     */
    public function __construct(
        DefaultUserOwnerSubscriber $defaultUserOwnerSubscriber,
        IntegrationFormSubscriber $integrationFormSubscriber
    ) {
        $this->defaultUserOwnerSubscriber = $defaultUserOwnerSubscriber;
        $this->integrationFormSubscriber  = $integrationFormSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->integrationFormSubscriber);
        $builder->addEventSubscriber($this->defaultUserOwnerSubscriber);

        $builder->add(
            self::TYPE_FIELD_NAME,
            'oro_integration_type_select',
            [
                'required' => true,
                'label'    => 'oro.integration.integration.type.label'
            ]
        );
        $builder->add('name', 'text', ['required' => true, 'label' => 'oro.integration.integration.name.label']);

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

        $builder->add(
            'defaultUserOwner',
            'oro_user_select',
            [
                'required' => true,
                'label'    => 'oro.integration.integration.default_user_owner.label',
                'tooltip'  => 'oro.integration.integration.default_user_owner.tooltip'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
