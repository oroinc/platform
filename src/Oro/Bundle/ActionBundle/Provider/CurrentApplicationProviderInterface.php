<?php

namespace Oro\Bundle\ActionBundle\Provider;

/**
 * Represents the provider for the current application info.
 */
interface CurrentApplicationProviderInterface
{
    public const DEFAULT_APPLICATION = 'default';

    /**
     * @param string[] $applications
     *
     * @return bool
     */
    public function isApplicationsValid(array $applications): bool;

    public function getCurrentApplication(): ?string;
}
