<?php

namespace Oro\Bundle\AddressBundle\Provider;

/**
 * Defines the contract for objects that can be aware of a root phone provider.
 *
 * Implementations of this interface allow objects to receive and store a reference
 * to a root phone provider, which serves as a central registry of all registered
 * phone providers. This enables objects to delegate phone number retrieval to the
 * appropriate provider based on the object type.
 */
interface RootPhoneProviderAwareInterface
{
    /**
     * Sets a root phone provider.
     * The root phone provider is a provider which known about all registered providers
     */
    public function setRootProvider(PhoneProviderInterface $rootProvider);
}
