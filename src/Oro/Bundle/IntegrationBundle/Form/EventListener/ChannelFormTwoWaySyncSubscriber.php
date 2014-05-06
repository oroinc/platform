<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

class ChannelFormTwoWaySyncSubscriber implements EventSubscriberInterface
{
    const REMOTE_WINS     = 'remote';
    const LOCAL_WINS      = 'local';

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
            FormEvents::PRE_SET_DATA  => 'preSet',
            FormEvents::PRE_SUBMIT  => 'preSubmit',
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

        if ($this->isNotEmpty($data->getType())) {
            if (true === $this->hasTwoWaySync($data->getType())) {
                $modifier = $this->getModifierClosure();
                $modifier($form);
            }
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

        if ($this->isNotEmpty($data['type'])) {
            if (true === $this->hasTwoWaySync($data['type'])) {
                $modifier = $this->getModifierClosure();
                $modifier($form);
            }
        }
    }

    /**
     * @param $data
     *
     * @return bool
     */
    private function isNotEmpty($data)
    {
        return !empty($data);
    }

    /**
     * @param string $type
     *
     * @return ArrayCollection
     */
    private function getRegisteredConnectorsTypes($type)
    {
        return $this->registry->getRegisteredConnectorsTypes($type);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function hasTwoWaySync($type)
    {
        $connectorsTypes = $this->getRegisteredConnectorsTypes($type);

        foreach ($connectorsTypes as $type) {
            if ($type instanceof TwoWaySyncConnectorInterface) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return callable
     */
    protected function getModifierClosure()
    {
        return function ($form) {
            $form->add(
                'isTwoWaySyncEnabled',
                'checkbox',
                [
                    'label'    => 'oro.integration.channel.two_way_sync_enabled.label',
                    'required' => false,
                ]
            );
            $form->add(
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
        };
    }
}
