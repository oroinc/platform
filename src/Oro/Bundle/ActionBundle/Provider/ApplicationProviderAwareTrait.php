<?php

namespace Oro\Bundle\ActionBundle\Provider;

/**
 * Provides functionality for managing an application provider dependency.
 *
 * This trait implements the {@see ApplicationProviderAwareInterface}, allowing classes
 * to store and access a current application provider instance.
 */
trait ApplicationProviderAwareTrait
{
    /** @var CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /**
     * @param CurrentApplicationProviderInterface $applicationProvider
     *
     * @return $this
     */
    public function setApplicationProvider(CurrentApplicationProviderInterface $applicationProvider)
    {
        $this->applicationProvider = $applicationProvider;

        return $this;
    }
}
