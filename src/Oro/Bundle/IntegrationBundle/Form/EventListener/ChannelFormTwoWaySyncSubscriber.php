<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

class ChannelFormTwoWaySyncSubscriber implements EventSubscriberInterface
{
    const REMOTE_WINS = 'remote';
    const LOCAL_WINS  = 'local';

    /** @var TypesRegistry */
    protected $registry;

    /**
     * @param TypesRegistry $registry
     */
    public function __construct(TypesRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (empty($data)) {
            return;
        }

        if (!empty($data->getType()) && $this->hasTwoWaySyncConnectors($data->getType())) {
            $modifier = $this->getModifierClosure();
            $modifier($form);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (empty($data)) {
            return;
        }

        if (!empty($data['type']) && $this->hasTwoWaySyncConnectors($data['type'])) {
            $modifier = $this->getModifierClosure();
            $modifier($form);
        }
    }

    /**
     * Checks whether channel has at least on connector that supports backward sync
     *
     * @param string $type
     *
     * @return bool
     */
    protected function hasTwoWaySyncConnectors($type)
    {
        $connectors = $this->registry->getRegisteredConnectorsTypes(
            $type,
            function (ConnectorInterface $connector) {
                return $connector instanceof TwoWaySyncConnectorInterface;
            }
        );

        return !$connectors->isEmpty();
    }

    /**
     * @return \Closure
     */
    protected function getModifierClosure()
    {
        return function (FormInterface $form) {
            $form->add(
                'isTwoWaySyncEnabled',
                'checkbox',
                [
                    'label'    => 'oro.integration.channel.is_two_way_sync_enabled.label',
                    'required' => false,
                ]
            );
            $form->add(
                'syncPriority',
                'choice',
                [
                    'label'    => 'oro.integration.channel.sync_priority.label',
                    'tooltip'  => 'oro.integration.channel.sync_priority.tooltip',
                    'required' => true,
                    'choices'  => [
                        self::REMOTE_WINS => 'oro.integration.channel.remote_wins.label',
                        self::LOCAL_WINS  => 'oro.integration.channel.local_wins.label'
                    ],
                ]
            );
        };
    }
}
