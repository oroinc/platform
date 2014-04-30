<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormSubscriber;
use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormTwoWaySyncSubscriber;

class ChannelType extends AbstractType
{
    const NAME            = 'oro_integration_channel_form';
    const TYPE_FIELD_NAME = 'type';
    const REMOTE_WINS     = 'remote';
    const LOCAL_WINS      = 'local';

    /** @var TypesRegistry */
    protected $registry;

    public function __construct(TypesRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new ChannelFormSubscriber($this->registry));

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
            'isTwoWaySyncEnabled',
            'checkbox',
            [
                'label'    => 'oro.integration.channel.two_way_sync_enabled.label',
                'required' => false,
            ]
        );

        $builder->add(
            'syncPriority',
            'choice',
            [
                'label'    => 'oro.integration.channel.sync_priority.label',
                'required' => false,
                'choices'  => [
                    self::REMOTE_WINS => 'oro.integration.channel.remote_wins.label',
                    self::LOCAL_WINS => 'oro.integration.channel.local_wins.label'
                ],
            ]
        );

        $builder->addEventSubscriber(new ChannelFormTwoWaySyncSubscriber($this->registry));
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
