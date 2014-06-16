<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormSubscriber;
use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormTwoWaySyncSubscriber;
use Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultUserOwnerSubscriber;

class ChannelType extends AbstractType
{
    const NAME            = 'oro_integration_channel_form';
    const TYPE_FIELD_NAME = 'type';

    /** @var TypesRegistry */
    protected $registry;

    /** @var SecurityFacade */
    protected $securityFacade;

    public function __construct(TypesRegistry $registry, SecurityFacade $securityFacade)
    {
        $this->registry       = $registry;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new ChannelFormSubscriber($this->registry));
        $builder->addEventSubscriber(new ChannelFormTwoWaySyncSubscriber($this->registry));
        $builder->addEventSubscriber(new DefaultUserOwnerSubscriber($this->securityFacade));

        $builder->add(
            self::TYPE_FIELD_NAME,
            'choice',
            [
                'required' => true,
                'choices'  => $this->registry->getAvailableChannelTypesChoiceList(),
                'label'    => 'oro.integration.channel.type.label'
            ]
        );
        $builder->add('name', 'text', ['required' => true, 'label' => 'oro.integration.channel.name.label']);

        // add transport type selector
        $builder->add(
            'transportType',
            'choice',
            [
                'label'       => 'oro.integration.channel.transport.label',
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
                'label'    => 'oro.integration.channel.connectors.label',
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
                'label'    => 'oro.integration.channel.default_user_owner.label',
                'tooltip'  => 'oro.integration.channel.default_user_owner.tooltip'
            ]
        );

        $builder->add(
            'organization',
            'oro_organization_select',
            [
                'required' => true,
                'label'    => 'oro.integration.integration.default_organization.label',
                'tooltip'  => 'oro.integration.integration.default_organization.tooltip'
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
