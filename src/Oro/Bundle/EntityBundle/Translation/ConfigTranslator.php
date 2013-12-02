<?php

namespace Oro\Bundle\EntityBundle\Translation;

use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\MessageCatalogue;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;

class ConfigTranslator extends BaseTranslator
{
    public function getTranslations(array $domains = array(), $locale = null)
    {
        $domains = $domains;
    }
}
