<?php

namespace Oro\Bundle\ActionBundle\Provider;

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
