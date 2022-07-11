<?php

namespace Oro\Bundle\TranslationBundle\Provider\Catalogue;

use Symfony\Component\Translation\MessageCatalogue;

/**
 * Translation catalogue loader that used in oro:translation:dump:file command.
 */
interface CatalogueLoaderInterface
{
    public function getLoaderName(): string;

    public function getCatalogue(string $locale): MessageCatalogue;
}
