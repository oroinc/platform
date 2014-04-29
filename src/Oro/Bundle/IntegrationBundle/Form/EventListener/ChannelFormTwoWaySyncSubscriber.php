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
            if (false === $this->hasTwoWaySync($data->getType())) {
                $form
                    ->remove('syncPriority')
                    ->remove('isTwoWaySyncEnable')
                ;
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
}
