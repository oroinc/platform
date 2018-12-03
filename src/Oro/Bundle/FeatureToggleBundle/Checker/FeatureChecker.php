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
    const STRATEGY_AFFIRMATIVE = 'affirmative';
    const STRATEGY_CONSENSUS = 'consensus';
    const STRATEGY_UNANIMOUS = 'unanimous';

    /**
     * @var VoterInterface[]
     */
    protected $voters;

    /**
     * @var ConfigurationManager
     */
    protected $configManager;

    /**
     * @var string
     */
    protected $strategy;

    /**
     * @var bool
     */
    protected $allowIfAllAbstainDecisions;

    /**
     * @var bool
     */
    protected $allowIfEqualGrantedDeniedDecisions;

    /**
     * @var array
     */
    protected $featuresStates = [];

    /**
     * @var array
     */
    protected $supportedStrategies = [
        self::STRATEGY_AFFIRMATIVE,
        self::STRATEGY_CONSENSUS,
        self::STRATEGY_UNANIMOUS
    ];

    /**
     * @param ConfigurationManager $configManager
     * @param array $voters
     * @param string $strategy
     * @param bool $allowIfAllAbstainDecisions
     * @param bool $allowIfEqualGrantedDeniedDecisions
     */
    public function __construct(
        ConfigurationManager $configManager,
        array $voters = [],
        $strategy = self::STRATEGY_UNANIMOUS,
        $allowIfAllAbstainDecisions = false,
        $allowIfEqualGrantedDeniedDecisions = true
    ) {
        if (!\in_array($strategy, $this->supportedStrategies, true)) {
            throw new \InvalidArgumentException(sprintf('The strategy "%s" is not supported.', $strategy));
        }

        $this->configManager = $configManager;

        $this->voters = $voters;
        $this->strategy = $strategy;
        $this->allowIfAllAbstainDecisions = (bool) $allowIfAllAbstainDecisions;
        $this->allowIfEqualGrantedDeniedDecisions = (bool) $allowIfEqualGrantedDeniedDecisions;
    }
    
    /**
     * Configures the voters.
     *
     * @param VoterInterface[] $voters An array of VoterInterface instances
     */
    public function setVoters(array $voters)
    {
        $this->voters = $voters;
    }

    /**
     * @param string $feature
     * @param object|int|null $scopeIdentifier
     * @return bool
     */
    public function isFeatureEnabled($feature, $scopeIdentifier = null)
    {
        return $this->checkFeatureState($feature, $scopeIdentifier);
    }

    /**
     * @param string $resource
     * @param string $resourceType
     * @param object|int|null $scopeIdentifier
     * @return bool
     */
    public function isResourceEnabled($resource, $resourceType, $scopeIdentifier = null)
    {
        $features = $this->configManager->getFeaturesByResource($resourceType, $resource);

        foreach ($features as $feature) {
            if (!$this->isFeatureEnabled($feature, $scopeIdentifier)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $resourceType
     *
     * @return array
     */
    public function getDisabledResourcesByType($resourceType)
    {
        $resources = $this->configManager->getResourcesByType($resourceType);

        $disabledResources = [];
        foreach ($resources as $resource => $features) {
            if (!ArrayUtil::some([$this, 'isFeatureEnabled'], $features)) {
                $disabledResources[] = $resource;
            }
        }

        return $disabledResources;
    }

    public function resetCache()
    {
        $this->featuresStates = [];
    }
    
    /**
     * @param string $feature
     * @param object|int|null $scopeIdentifier
     * @return bool
     */
    protected function checkFeatureState($feature, $scopeIdentifier = null)
    {
        $cacheKey = $this->getCacheKey($feature, $scopeIdentifier);
        if (!array_key_exists($cacheKey, $this->featuresStates)) {
            $this->featuresStates[$cacheKey] = $this->check($feature, $scopeIdentifier);
        }

        return $this->featuresStates[$cacheKey];
    }

    /**
     * @param string $feature
     * @param object|int|null $scopeIdentifier
     * @return bool
     */
    protected function check($feature, $scopeIdentifier = null)
    {
        switch ($this->configManager->get($feature, 'strategy', $this->strategy)) {
            case self::STRATEGY_AFFIRMATIVE:
                return $this->checkAffirmativeStrategy($feature, $scopeIdentifier);

            case self::STRATEGY_CONSENSUS:
                return $this->checkConsensusStrategy($feature, $scopeIdentifier);

            case self::STRATEGY_UNANIMOUS:
                return $this->checkUnanimousStrategy($feature, $scopeIdentifier);

            default:
                return true;
        }
    }

    /**
     * @param string $feature
     * @param object|int|null $scopeIdentifier
     * @return bool
     */
    protected function checkAffirmativeStrategy($feature, $scopeIdentifier = null)
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

    /**
     * @param string $feature
     * @param object|int|null $scopeIdentifier
     * @return bool
     */
    protected function checkConsensusStrategy($feature, $scopeIdentifier = null)
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
    
    /**
     * @param $feature
     * @param object|int|null $scopeIdentifier
     * @return bool
     */
    protected function checkUnanimousStrategy($feature, $scopeIdentifier = null)
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

    /**
     * @param string $feature
     * @param null|int|object $scopeIdentifier
     * @return string
     */
    protected function getCacheKey($feature, $scopeIdentifier = null)
    {
        $cacheKey = $feature;
        if ($scopeIdentifier) {
            if (is_scalar($scopeIdentifier)) {
                $cacheKey .= ':' . $scopeIdentifier;
            }
            if (is_object($scopeIdentifier)) {
                $cacheKey .= ':' . spl_object_hash($scopeIdentifier);
            }
        }

        return $cacheKey;
    }
}
