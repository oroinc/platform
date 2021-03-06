<?php

namespace Oro\Bundle\AddressBundle\Provider;

interface RootPhoneProviderAwareInterface
{
    /**
     * Sets a root phone provider.
     * The root phone provider is a provider which known about all registered providers
     */
    public function setRootProvider(PhoneProviderInterface $rootProvider);
}
