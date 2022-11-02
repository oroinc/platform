<?php

namespace Oro\Bundle\UserBundle\Provider;

/**
 * Represents a service that provides basic user info for logging purposes.
 */
interface UserLoggingInfoProviderInterface
{
    public function getUserLoggingInfo(mixed $user): array;
}
