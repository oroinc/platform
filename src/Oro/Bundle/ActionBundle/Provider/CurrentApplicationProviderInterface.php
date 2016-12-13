<?php

namespace Oro\Bundle\ActionBundle\Provider;

interface CurrentApplicationProviderInterface
{
    const DEFAULT_APPLICATION = 'default';

    /**
     * @param array $applications
     *
     * @return bool
     */
    public function isApplicationsValid(array $applications);

    /**
     * @return string|null
     */
    public function getCurrentApplication();
}
