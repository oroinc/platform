<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Doctrine\Common\Cache\Cache;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;

class CacheContextConfigurator implements ContextConfiguratorInterface
{
    const MAX_MODIFICATION_DATE_PARAM = 'modification_date';

    /** @var Cache */
    private $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setDefaults([self::MAX_MODIFICATION_DATE_PARAM => ''])
            ->setAllowedTypes([self::MAX_MODIFICATION_DATE_PARAM => 'string']);

        $date = $this->cache->fetch(ThemeResourceProvider::CACHE_LAST_MODIFICATION_DATE);

        if ($date) {
            $context->set(self::MAX_MODIFICATION_DATE_PARAM, $date->format(\DateTime::COOKIE));
        }
    }
}
