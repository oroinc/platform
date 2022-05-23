<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Check state of the feature and it's parts.
 */
class FeatureChecker
{
    public const STRATEGY_AFFIRMATIVE = 'affirmative';
    public const STRATEGY_CONSENSUS = 'consensus';
    public const STRATEGY_UNANIMOUS = 'unanimous';

    /** @var iterable|VoterInterface[] */
    private iterable $voters;
    private ConfigurationManager $configManager;
    private string $strategy;
    private bool $allowIfAllAbstainDecisions;
    private bool $allowIfEqualGrantedDeniedDecisions;
    private array $featuresStates = [];

    public function __construct(
        ConfigurationManager $configManager,
        iterable $voters,
        string $strategy,
        bool $allowIfAllAbstainDecisions,
        bool $allowIfEqualGrantedDeniedDecisions
    ) {
        if (self::STRATEGY_UNANIMOUS !== $strategy
            && self::STRATEGY_AFFIRMATIVE !== $strategy
            && self::STRATEGY_CONSENSUS !== $strategy
        ) {
            throw new \InvalidArgumentException(sprintf('The strategy "%s" is not supported.', $strategy));
        }

        $this->configManager = $configManager;
        $this->voters = $voters;
        $this->strategy = $strategy;
        $this->allowIfAllAbstainDecisions = $allowIfAllAbstainDecisions;
        $this->allowIfEqualGrantedDeniedDecisions = $allowIfEqualGrantedDeniedDecisions;
    }

    public function isFeatureEnabled(string $feature, object|int|null $scopeIdentifier = null): bool
    {
        $cacheKey = $this->getCacheKey($feature, $scopeIdentifier);
        if (!\array_key_exists($cacheKey, $this->featuresStates)) {
            $this->featuresStates[$cacheKey] = $this->check($feature, $scopeIdentifier);
        }

        return $this->featuresStates[$cacheKey];
    }

    public function isResourceEnabled(
        string $resource,
        string $resourceType,
        object|int|null $scopeIdentifier = null
    ): bool {
        $features = $this->configManager->getFeaturesByResource($resourceType, $resource);
        foreach ($features as $feature) {
            if (!$this->isFeatureEnabled($feature, $scopeIdentifier)) {
                return false;
            }
        }

        return true;
    }

    public function getDisabledResourcesByType(string $resourceType): array
    {
        $disabledResources = [];
        $resources = $this->configManager->getResourcesByType($resourceType);
        foreach ($resources as $resource => $features) {
            if (!ArrayUtil::some([$this, 'isFeatureEnabled'], $features)) {
                $disabledResources[] = $resource;
            }
        }

        return $disabledResources;
    }

    public function resetCache(): void
    {
        $this->featuresStates = [];
    }

    private function check(string $feature, object|int|null $scopeIdentifier): bool
    {
        $strategy = $this->configManager->get($feature, 'strategy', $this->strategy);
        if (self::STRATEGY_UNANIMOUS === $strategy) {
            return $this->checkUnanimousStrategy($feature, $scopeIdentifier);
        }
        if (self::STRATEGY_AFFIRMATIVE === $strategy) {
            return $this->checkAffirmativeStrategy($feature, $scopeIdentifier);
        }
        if (self::STRATEGY_CONSENSUS === $strategy) {
            return $this->checkConsensusStrategy($feature, $scopeIdentifier);
        }

        return true;
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
}
