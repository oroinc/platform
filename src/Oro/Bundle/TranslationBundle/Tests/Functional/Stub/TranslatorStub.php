<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Stub;

use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslatorStub extends Translator
{
    /** @var callable|null */
    private $rebuildCacheCallback;

    /** @var callable|null */
    private $getTranslationsCallback;

    /**
     * @param callable|null $rebuildCache
     */
    public function setRebuildCache(?callable $rebuildCache): void
    {
        $this->rebuildCacheCallback = $rebuildCache;
    }

    /**
     * {@inheritdoc}
     */
    public function rebuildCache(): void
    {
        if ($this->rebuildCacheCallback) {
            ($this->rebuildCacheCallback)();
        } else {
            parent::rebuildCache();
        }
    }

    /**
     * @param callable|null $getTranslations
     */
    public function setGetTranslations(?callable $getTranslations): void
    {
        $this->getTranslationsCallback = $getTranslations;
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslations(array $domains = [], $locale = null): array
    {
        $callback = $this->getTranslationsCallback;

        return $callback ? $callback($domains, $locale) : parent::getTranslations($domains, $locale);
    }
}
