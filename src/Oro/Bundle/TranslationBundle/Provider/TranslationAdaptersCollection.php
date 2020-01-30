<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Container\ContainerInterface;

/**
 * The registry of translation adapters.
 */
class TranslationAdaptersCollection
{
    /** @var ContainerInterface */
    private $translationAdapters;

    /**
     * @param ContainerInterface $translationAdapters
     */
    public function __construct(ContainerInterface $translationAdapters)
    {
        $this->translationAdapters = $translationAdapters;
    }

    /**
     * @param string $name
     *
     * @return APIAdapterInterface|null
     */
    public function getAdapter(string $name): ?APIAdapterInterface
    {
        if (!$this->translationAdapters->has($name)) {
            return null;
        }

        return $this->translationAdapters->get($name);
    }
}
