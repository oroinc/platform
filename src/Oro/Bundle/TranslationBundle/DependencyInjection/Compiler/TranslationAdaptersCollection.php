<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\Provider\APIAdapterInterface;

/**
 * Registry of all translation adapters available for Oro
 */
class TranslationAdaptersCollection
{
    /** @var APIAdapterInterface[] */
    private $translationAdapters;

    /**
     * @param APIAdapterInterface $adapter
     * @param string $name
     * @return $this
     */
    public function addAdapter(APIAdapterInterface $adapter, string $name)
    {
        $this->translationAdapters[$name] = $adapter;

        return $this;
    }

    /**
     * @param string $name
     * @return null|APIAdapterInterface
     */
    public function getAdapter(string $name)
    {
        return $this->translationAdapters[$name] ?? null;
    }
}
