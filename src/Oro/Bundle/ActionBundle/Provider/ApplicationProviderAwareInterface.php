<?php

namespace Oro\Bundle\ActionBundle\Provider;

interface ApplicationProviderAwareInterface
{
    /**
     * @param CurrentApplicationProviderInterface $applicationProvider
     *
     * @return $this
     */
    public function setApplicationProvider(CurrentApplicationProviderInterface $applicationProvider);
}
