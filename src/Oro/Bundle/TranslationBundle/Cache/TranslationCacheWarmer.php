<?php

namespace Oro\Bundle\TranslationBundle\Cache;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class TranslationCacheWarmer implements CacheWarmerInterface, ServiceSubscriberInterface
{
    /**
     * @var TranslationsCacheWarmer
     */
    protected $innerWarmer;

    /**
     * @var TranslationStrategyProvider
     */
    protected $strategyProvider;

    /**
     * @param TranslationsCacheWarmer $innerWarmer
     * @param TranslationStrategyProvider $strategyProvider
     */
    public function __construct(TranslationsCacheWarmer $innerWarmer, TranslationStrategyProvider $strategyProvider)
    {
        $this->innerWarmer = $innerWarmer;
        $this->strategyProvider = $strategyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $originalStrategy = $this->strategyProvider->getStrategy();
        foreach ($this->strategyProvider->getStrategies() as $strategy) {
            $this->strategyProvider->setStrategy($strategy);
            $this->innerWarmer->warmUp($cacheDir);
        }
        $this->strategyProvider->setStrategy($originalStrategy);
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return $this->innerWarmer->isOptional();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return TranslationsCacheWarmer::getSubscribedServices();
    }
}
