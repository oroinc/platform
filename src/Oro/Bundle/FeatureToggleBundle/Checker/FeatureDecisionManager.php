<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

/**
 * Makes decisions whether a feature is enabled or not.
 */
class FeatureDecisionManager implements FeatureDecisionManagerInterface
{
    private const STRATEGY_UNANIMOUS = 'unanimous';
    private const STRATEGY_AFFIRMATIVE = 'affirmative';
    private const STRATEGY_CONSENSUS = 'consensus';

    private ConfigurationManager $configManager;
    /** @var iterable|VoterInterface[] */
    private iterable $voters;
    private string $strategy;
    private bool $allowIfAllAbstainDecisions;
    private bool $allowIfEqualGrantedDeniedDecisions;
    private array $cache = [];

    public function __construct(
        ConfigurationManager $configManager,
        iterable $voters,
        string $strategy,
        bool $allowIfAllAbstainDecisions,
        bool $allowIfEqualGrantedDeniedDecisions
    ) {
        $this->configManager = $configManager;
        $this->voters = $voters;
        $this->strategy = $strategy;
        $this->allowIfAllAbstainDecisions = $allowIfAllAbstainDecisions;
        $this->allowIfEqualGrantedDeniedDecisions = $allowIfEqualGrantedDeniedDecisions;
    }

    /**
     * {@inheritDoc}
     */
    public function decide(string $feature, object|int|null $scopeIdentifier): bool
    {
        $cacheKey = $this->getCacheKey($feature, $scopeIdentifier);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $result = true;
        $strategy = $this->configManager->get($feature, 'strategy', $this->strategy);
        if (self::STRATEGY_UNANIMOUS === $strategy) {
            $result = $this->checkUnanimousStrategy($feature, $scopeIdentifier);
        } elseif (self::STRATEGY_AFFIRMATIVE === $strategy) {
            $result = $this->checkAffirmativeStrategy($feature, $scopeIdentifier);
        } elseif (self::STRATEGY_CONSENSUS === $strategy) {
            $result = $this->checkConsensusStrategy($feature, $scopeIdentifier);
        }
        $this->cache[$cacheKey] = $result;

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->cache = [];
    }

    private function getCacheKey(string $feature, object|int|null $scopeIdentifier): string
    {
        $cacheKey = $feature;
        if ($scopeIdentifier) {
            if (is_scalar($scopeIdentifier)) {
                $cacheKey .= ':' . $scopeIdentifier;
            }
            if (\is_object($scopeIdentifier)) {
                $cacheKey .= ':' . spl_object_hash($scopeIdentifier);
            }
        }

        return $cacheKey;
    }

    private function checkUnanimousStrategy(string $feature, object|int|null $scopeIdentifier): bool
    {
        $enabled = 0;
        foreach ($this->voters as $voter) {
            $result = $voter->vote($feature, $scopeIdentifier);
            switch ($result) {
                case VoterInterface::FEATURE_ENABLED:
                    ++$enabled;
                    break;
                case VoterInterface::FEATURE_DISABLED:
                    return false;
                default:
                    break;
            }
        }

        if ($enabled > 0) {
            return true;
        }

        return $this->configManager->get($feature, 'allow_if_all_abstain', $this->allowIfAllAbstainDecisions);
    }

    private function checkAffirmativeStrategy(string $feature, object|int|null $scopeIdentifier): bool
    {
        $disabled = 0;
        foreach ($this->voters as $voter) {
            $result = $voter->vote($feature, $scopeIdentifier);
            switch ($result) {
                case VoterInterface::FEATURE_ENABLED:
                    return true;
                case VoterInterface::FEATURE_DISABLED:
                    ++$disabled;
                    break;
                default:
                    break;
            }
        }

        if ($disabled > 0) {
            return false;
        }

        return $this->configManager->get($feature, 'allow_if_all_abstain', $this->allowIfAllAbstainDecisions);
    }

    private function checkConsensusStrategy(string $feature, object|int|null $scopeIdentifier): bool
    {
        $enabled = 0;
        $disabled = 0;
        foreach ($this->voters as $voter) {
            $result = $voter->vote($feature, $scopeIdentifier);
            switch ($result) {
                case VoterInterface::FEATURE_ENABLED:
                    ++$enabled;
                    break;
                case VoterInterface::FEATURE_DISABLED:
                    ++$disabled;
                    break;
            }
        }

        if ($enabled > $disabled) {
            return true;
        }
        if ($disabled > $enabled) {
            return false;
        }

        if ($enabled > 0) {
            return $this->configManager->get(
                $feature,
                'allow_if_equal_granted_denied',
                $this->allowIfEqualGrantedDeniedDecisions
            );
        }

        return $this->configManager->get($feature, 'allow_if_all_abstain', $this->allowIfAllAbstainDecisions);
    }
}
