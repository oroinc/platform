<?php

namespace Oro\Bundle\TranslationBundle\Provider\Catalogue;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Provider\TranslationProvider;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Catalogue loader that gets the translation catalogue from the database.
 */
class DatabaseCatalogueLoader implements CatalogueLoaderInterface
{
    private TranslationProvider $provider;

    public function __construct(TranslationProvider $provider)
    {
        $this->provider = $provider;
    }

    #[\Override]
    public function getLoaderName(): string
    {
        return 'database';
    }

    #[\Override]
    public function getCatalogue(string $locale): MessageCatalogue
    {
        return $this->provider->getMessageCatalogueByLocaleAndScope($locale);
    }
}
