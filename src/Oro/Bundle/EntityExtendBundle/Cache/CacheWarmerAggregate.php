<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\EntityBundle\Tools\CheckDatabaseStateManager;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate as SymfonyCacheWarmerAggregate;

/**
 * Replaces Symfony's CacheWarmerAggregate to not warmup caches if extend caches are not up to date.
 * This class extends {@see \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate} for compatibility with
 * {@see \Symfony\Bundle\FrameworkBundle\Command\CacheWarmupCommand::__construct}.
 */
class CacheWarmerAggregate extends SymfonyCacheWarmerAggregate
{
    /** @var ServiceLink */
    private $cacheWarmerLink;

    /** @var ServiceLink */
    private $extendCacheWarmerLink;

    /** @var bool */
    private $optionalsEnabled = false;

    /** @var bool */
    private $onlyOptionalsEnabled = false;

    public function __construct(
        ServiceLink $cacheWarmerLink,
        ServiceLink $extendCacheWarmerLink,
        CheckDatabaseStateManager $checkDatabaseStateManager
    ) {
        $this->cacheWarmerLink = $cacheWarmerLink;
        $this->extendCacheWarmerLink = $extendCacheWarmerLink;
        $checkDatabaseStateManager->clearState();
    }

    /**
     * @see \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate::enableOptionalWarmers
     */
    public function enableOptionalWarmers()
    {
        $this->optionalsEnabled = true;
    }

    /**
     * @see \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate::enableOnlyOptionalWarmers
     */
    public function enableOnlyOptionalWarmers()
    {
        $this->optionalsEnabled = true;
        $this->onlyOptionalsEnabled = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir): array
    {
        $cacheWarmerLink = $this->cacheWarmerLink;
        if (CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)
            || CommandExecutor::isCurrentCommand('oro:install', true)
            || CommandExecutor::isCurrentCommand('oro:assets:install', true)
        ) {
            $cacheWarmerLink = $this->extendCacheWarmerLink;
        }
        /** @var SymfonyCacheWarmerAggregate $cacheWarmer */
        $cacheWarmer = $cacheWarmerLink->getService();
        if ($this->onlyOptionalsEnabled) {
            $cacheWarmer->enableOnlyOptionalWarmers();
        } elseif ($this->optionalsEnabled) {
            $cacheWarmer->enableOptionalWarmers();
        }

        return $cacheWarmer->warmUp($cacheDir);
    }
}
