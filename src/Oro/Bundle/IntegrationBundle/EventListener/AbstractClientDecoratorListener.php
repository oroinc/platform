<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Event\ClientCreatedAfterEvent;

abstract class AbstractClientDecoratorListener
{
    /**
     * @param ClientCreatedAfterEvent $event
     *
     * @return void
     */
    public function onClientCreated(ClientCreatedAfterEvent $event)
    {
        $configuration = $this->getConfigurationFromEvent($event);

        if ($this->isApplicable($event) && $this->isEnabled($configuration)) {
            $this->attachDecorator($event, $configuration);
        }
    }

    /**
     * @param ClientCreatedAfterEvent $event
     * @param array                   $configuration
     *
     * @return mixed
     */
    abstract protected function attachDecorator(ClientCreatedAfterEvent $event, array $configuration);

    /**
     * @return array
     */
    abstract protected function getDefaultConfigurationParameters();

    /**
     * @return string
     */
    abstract protected function getConfigurationKey();

    /**
     * @param array $configuration
     *
     * @return mixed
     */
    abstract protected function isEnabled(array $configuration);

    /**
     * @param ClientCreatedAfterEvent $event
     *
     * @return mixed
     */
    abstract protected function isApplicable(ClientCreatedAfterEvent $event);

    /**
     * @param ClientCreatedAfterEvent $event
     *
     * @return array
     */
    protected function getConfigurationFromEvent(ClientCreatedAfterEvent $event)
    {
        $params = $this->getDefaultConfigurationParameters();

        $clientConfiguration = $event->getAdditionalParameterBag();
        if ($clientConfiguration->has($this->getConfigurationKey())) {
            $params = array_merge(
                $params,
                $clientConfiguration->get($this->getConfigurationKey())
            );
        }

        return $params;
    }
}
