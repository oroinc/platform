<?php

namespace Oro\Component\Testing\Unit\Form\EventListener\Stub;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class AddressCountryAndRegionSubscriberStub extends AddressCountryAndRegionSubscriber
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}
