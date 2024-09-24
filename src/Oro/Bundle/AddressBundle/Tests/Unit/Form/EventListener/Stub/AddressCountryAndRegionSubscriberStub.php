<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

/**
 * This stub disables logic of original AddressCountryAndRegionSubscriber
 */
class AddressCountryAndRegionSubscriberStub extends AddressCountryAndRegionSubscriber
{
    public function __construct()
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
