<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Cache for Enum values
 */
class EnumTranslationCache
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     * @param Cache $cache
     */
    public function __construct(TranslatorInterface $translator, Cache $cache)
    {
        $this->translator = $translator;
        $this->cache = $cache;
    }

    /**
     * Check that cache contains values
     *
     * @param string $enumValueEntityClass
     *
     * @return bool
     */
    public function contains(string $enumValueEntityClass): bool
    {
        $key = $this->getKey($enumValueEntityClass);

        return $this->cache->contains($key);
    }

    /**
     * Fetch values from a cache
     *
     * @param string $enumValueEntityClass
     *
     * @return array
     *         key   => enum value entity class name
     *         value => array // values are sorted by priority
     *             key   => enum value id
     *             value => enum value name
     */
    public function fetch(string $enumValueEntityClass): array
    {
        $key = $this->getKey($enumValueEntityClass);
        $value = $this->cache->fetch($key);

        return false !== $value ? $value : [];
    }

    /**
     * Save values
     *
     * @param string $enumValueEntityClass
     * @param array $items
     *              key   => enum value entity class name
     *              value => array // values are sorted by priority
     *                  key   => enum value id
     *                  value => enum value name
     */
    public function save(string $enumValueEntityClass, array $items)
    {
        $this->cache->save($this->getKey($enumValueEntityClass), $items);
    }

    /**
     * Invalidate a cache by class of the enum value entity
     *
     * @param string $enumValueEntityClass
     */
    public function invalidate(string $enumValueEntityClass)
    {
        $key = $this->getKey($enumValueEntityClass);

        $this->cache->delete($key);
    }

    /**
     * @param string $enumValueEntityClass
     * @return string
     */
    private function getKey(string $enumValueEntityClass): string
    {
        return sprintf('%s|%s', $enumValueEntityClass, $this->translator->getLocale());
    }
}
