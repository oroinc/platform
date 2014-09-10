<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Form;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ImmutableFormEventSubscriber implements EventSubscriberInterface
{

 protected static $events = [];

    protected $wrapped;

    public function __construct(EventSubscriberInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public static function getSubscribedEvents()
    {
        return self::$events;
    }

    public static function setSubscribedEvents($events)
    {
        self::$events = $events;
    }

    public function __call($method, $args)
    {
        if (!method_exists($this->wrapped, $method)) {
            throw new Exception("unknown method [$method]");
        }

        return call_user_func_array(
            array($this->wrapped, $method),
            $args
        );
    }
}
