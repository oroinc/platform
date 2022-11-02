<?php

namespace Oro\Component\Config\Cache;

/**
 * Delegates the checking a state of a configuration cache to child checkers.
 */
class ChainConfigCacheState implements ConfigCacheStateInterface
{
    /** @var ConfigCacheStateInterface[] */
    private $states;

    /**
     * @param ConfigCacheStateInterface[] $states
     */
    public function __construct(array $states = [])
    {
        $this->states = $states;
    }

    public function addConfigCacheState(ConfigCacheStateInterface $state): void
    {
        $this->states[] = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function isCacheFresh(?int $timestamp): bool
    {
        foreach ($this->states as $state) {
            if (!$state->isCacheFresh($timestamp)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTimestamp(): ?int
    {
        $result = null;
        foreach ($this->states as $state) {
            $timestamp = $state->getCacheTimestamp();
            if (null !== $timestamp && (null === $result || $result < $timestamp)) {
                $result = $timestamp;
            }
        }

        return $result;
    }
}
