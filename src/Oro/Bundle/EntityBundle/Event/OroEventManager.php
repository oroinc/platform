<?php

namespace Oro\Bundle\EntityBundle\Event;

use Symfony\Bridge\Doctrine\ContainerAwareEventManager;

use Doctrine\Common\EventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroEventManager extends ContainerAwareEventManager
{
    /**
     * @var array
     */
    protected $disabledListenerRegexps = array();

    /**
     * @var array
     */
    protected $disabledListeners = array();

    /**
     * @var ContainerInterface
     */
    protected $serviceContainer;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->serviceContainer = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchEvent($eventName, EventArgs $eventArgs = null)
    {
        $needExtraProcessing = $this->hasDisabledListeners() && $this->hasListeners($eventName);

        if ($needExtraProcessing) {
            $this->preDispatch($eventName);
        }

        parent::dispatchEvent($eventName, $eventArgs);

        if ($needExtraProcessing) {
            $this->postDispatch($eventName);
        }
    }

    /**
     * @param string $classNameRegexp
     */
    public function disableListeners($classNameRegexp = '.*')
    {
        $this->disabledListenerRegexps[] = $classNameRegexp;
    }

    /**
     * Clear all rules for disabling listeners
     */
    public function clearDisabledListeners()
    {
        $this->disabledListenerRegexps = array();
    }

    /**
     * @return bool
     */
    public function hasDisabledListeners()
    {
        return !empty($this->disabledListenerRegexps);
    }

    /**
     * @param object $listener
     * @return bool
     */
    protected function isListenerEnabled($listener)
    {
        $listenerClass = get_class($listener);

        foreach ($this->disabledListenerRegexps as $regexp) {
            if (preg_match('~' . $regexp . '~', $listenerClass)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $event
     */
    protected function preDispatch($event)
    {
        $listeners = $this->getListeners($event);
        foreach ($listeners as $listener) {
            $unmodifiedListener = $listener;
            if (is_string($listener)) {
                $listener = $this->serviceContainer->get($listener);
            }

            if (!$this->isListenerEnabled($listener)) {
                $this->disabledListeners[$event][] = $listener;
                $this->removeEventListener($event, $unmodifiedListener);
            }
        }
    }

    /**
     * @param string $event
     */
    protected function postDispatch($event)
    {
        if (empty($this->disabledListeners[$event])) {
            return;
        }

        foreach ($this->disabledListeners[$event] as $listener) {
            $this->addEventListener($event, $listener);
        }

        unset($this->disabledListeners[$event]);
    }
}
