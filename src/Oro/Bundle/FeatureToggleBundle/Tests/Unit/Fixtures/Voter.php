<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class Voter implements VoterInterface
{
    private array $strategyByFeature;
    private string $defaultStrategy;

    public function __construct(array $strategyByFeature, int $defaultStrategy = VoterInterface::FEATURE_ABSTAIN)
    {
        $this->strategyByFeature = $strategyByFeature;
        $this->defaultStrategy   = $defaultStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        return \array_key_exists($feature, $this->strategyByFeature)
            ? $this->strategyByFeature[$feature]
            : $this->defaultStrategy;
    }
}
