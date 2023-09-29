<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * This loader is used to load empty translation catalogue.
 */
class EmptyArrayLoader extends ArrayLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages'): MessageCatalogue
    {
        return new MessageCatalogue($locale);
    }
}
