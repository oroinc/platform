<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageCatalogue;

class EmptyArrayLoader extends ArrayLoader
{
    /**
     * @param mixed $resource
     * @param string $locale
     * @param string $domain
     * @return MessageCatalogue
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        return new MessageCatalogue($locale);
    }
}
