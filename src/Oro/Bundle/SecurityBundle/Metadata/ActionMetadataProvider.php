<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * The provider for action (another name is a capability) related security metadata.
 */
class ActionMetadataProvider implements WarmableConfigCacheInterface, ClearableConfigCacheInterface
{
    private const CACHE_KEY = 'data';

    /** @var AclAnnotationProvider */
    private $annotationProvider;

    /** @var TranslatorInterface */
    private $translator;

    /** @var CacheProvider */
    private $cache;

    /** @var array [action name => ActionMetadata, ...] */
    private $localCache;

    /**
     * @param AclAnnotationProvider $annotationProvider
     * @param TranslatorInterface   $translator
     * @param CacheProvider         $cache
     */
    public function __construct(
        AclAnnotationProvider $annotationProvider,
        TranslatorInterface $translator,
        CacheProvider $cache
    ) {
        $this->annotationProvider = $annotationProvider;
        $this->translator = $translator;
        $this->cache = $cache;
    }

    /**
     * Checks whether an action with the given name is defined.
     *
     * @param  string $actionName The entity class name
     * @return bool
     */
    public function isKnownAction($actionName)
    {
        $this->ensureMetadataLoaded();

        return isset($this->localCache[$actionName]);
    }

    /**
     * Gets metadata for all actions.
     *
     * @return ActionMetadata[]
     */
    public function getActions()
    {
        $this->ensureMetadataLoaded();

        return array_values($this->localCache);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->localCache = $this->loadMetadata();
        $this->cache->save(
            self::CACHE_KEY,
            [$this->annotationProvider->getCacheTimestamp(), $this->localCache]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->localCache = null;
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * Makes sure that metadata are loaded and cached
     */
    private function ensureMetadataLoaded()
    {
        if (null !== $this->localCache) {
            return;
        }

        $cachedData = $this->cache->fetch(self::CACHE_KEY);
        if (false !== $cachedData) {
            list($timestamp, $data) = $cachedData;
            if ($this->annotationProvider->isCacheFresh($timestamp)) {
                $this->localCache = $data;
            }
        }
        if (null === $this->localCache) {
            $this->localCache = $this->loadMetadata();
            $this->cache->save(
                self::CACHE_KEY,
                [$this->annotationProvider->getCacheTimestamp(), $this->localCache]
            );
        }
    }

    /**
     * Loads metadata and save them in cache
     *
     * @return array
     */
    private function loadMetadata()
    {
        $data = [];
        $annotations = $this->annotationProvider->getAnnotations('action');
        foreach ($annotations as $annotation) {
            $description = $annotation->getDescription();
            if ($description) {
                $description = $this->translator->trans($description);
            }
            $annotationId = $annotation->getId();
            $data[$annotationId] = new ActionMetadata(
                $annotationId,
                $annotation->getGroup(),
                $this->translator->trans($annotation->getLabel()),
                $description,
                $annotation->getCategory()
            );
        }

        return $data;
    }
}
