<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Stub for FeatureChecker
 */
class FeatureCheckerStub extends FeatureChecker
{
    private ?bool $resourceEnabled;

    public function setResourceEnabled(?bool $enabled): void
    {
        $this->resourceEnabled = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceEnabled($resource, $resourceType, $scopeIdentifier = null)
    {
        return $this->resourceEnabled ?? parent::isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }
}
