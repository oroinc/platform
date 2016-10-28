<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class Voter implements VoterInterface
{
    /** @var array */
    protected $strategyByFeature;

    /** @var string */
    protected $defaultStrategy;

    /**
     * @param array $strategyByFeature
     * @param int   $defaultStrategy
     */
    public function __construct(array $strategyByFeature, $defaultStrategy = VoterInterface::FEATURE_ABSTAIN)
    {
        $this->strategyByFeature = $strategyByFeature;
        $this->defaultStrategy   = $defaultStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        return array_key_exists($feature, $this->strategyByFeature)
            ? $this->strategyByFeature[$feature]
            : $this->defaultStrategy;
    }
}
