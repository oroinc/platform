<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageCatalogue;

class EmptyArrayLoader extends ArrayLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        return new MessageCatalogue($locale);
    }
}
