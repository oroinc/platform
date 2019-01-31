<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * The provider for action (another name is a capability) related secutity metadata.
 */
class ActionMetadataProvider implements WarmableConfigCacheInterface, ClearableConfigCacheInterface
{
    private const CACHE_KEY = 'data';

    /** @var AclAnnotationProvider */
    protected $annotationProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var CacheProvider */
    protected $cache;

    /** @var array [action name => ActionMetadata, ...] */
    protected $localCache;

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
        $this->loadMetadata();
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
    protected function ensureMetadataLoaded()
    {
        if (null === $this->localCache) {
            $data = $this->cache->fetch(self::CACHE_KEY);
            if (false !== $data) {
                $this->localCache = $data;
            } else {
                $this->loadMetadata();
            }
        }
    }

    /**
     * Loads metadata and save them in cache
     */
    protected function loadMetadata()
    {
        $data = [];
        foreach ($this->annotationProvider->getAnnotations('action') as $annotation) {
            $description = $annotation->getDescription();
            if ($description) {
                $description = $this->translator->trans($description);
            }
            $data[$annotation->getId()] = new ActionMetadata(
                $annotation->getId(),
                $annotation->getGroup(),
                $this->translator->trans($annotation->getLabel()),
                $description,
                $annotation->getCategory()
            );
        }

        $this->cache->save(self::CACHE_KEY, $data);

        $this->localCache = $data;
    }
}
