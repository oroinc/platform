<?php

namespace Oro\Bundle\ActionBundle\Provider;

/**
 * Defines the contract for objects that can be injected with an application provider.
 */
interface ApplicationProviderAwareInterface
{
    /**
     * @param CurrentApplicationProviderInterface $applicationProvider
     *
     * @return $this
     */
    public function setApplicationProvider(CurrentApplicationProviderInterface $applicationProvider);
}
