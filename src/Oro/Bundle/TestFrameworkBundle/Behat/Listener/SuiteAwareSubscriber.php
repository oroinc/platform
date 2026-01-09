<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\SuiteAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Injects the current test suite into suite-aware services.
 *
 * This event subscriber listens for suite initialization events and injects the current
 * suite into all registered services that implement SuiteAwareInterface, allowing them
 * to access suite-specific configuration.
 */
class SuiteAwareSubscriber implements EventSubscriberInterface
{
    /** @var  SuiteAwareInterface[] */
    protected $services;

    /**
     * @param SuiteAwareInterface[] $services
     */
    public function __construct(array $services)
    {
        $this->services = $services;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeSuiteTested::BEFORE => ['injectSuite', 5],
        ];
    }

    public function injectSuite(BeforeSuiteTested $event)
    {
        foreach ($this->services as $service) {
            $service->setSuite($event->getSuite());
        }
    }
}
