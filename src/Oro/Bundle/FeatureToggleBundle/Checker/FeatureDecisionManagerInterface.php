<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Represents a service that is responsible to make decisions whether a feature is enabled or not.
 */
interface FeatureDecisionManagerInterface extends ResetInterface
{
    /**
     * Decides whether the feature is enabled or not.
     */
    public function decide(string $feature, object|int|null $scopeIdentifier): bool;
}
