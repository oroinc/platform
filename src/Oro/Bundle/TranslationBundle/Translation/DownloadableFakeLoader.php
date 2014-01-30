<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DownloadableFakeLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        return new MessageCatalogue($locale);
    }
}
