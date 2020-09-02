<?php

namespace Oro\Bundle\UIBundle\Tools\HTMLPurifier;

use Oro\Bundle\TranslationBundle\Translation\TranslatorAwareInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslatorAwareTrait;

/**
 * Represents a language and defines localizable string formatting and
 * other functions, as well as the localized messages for HTML Purifier.
 */
class Language extends \HTMLPurifier_Language implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load()
    {
        if ($this->_loaded) {
            return;
        }
        $factory = LanguageFactory::instance();
        $factory->setTranslator($this->translator);
        $factory->loadLanguage($this->code);

        foreach ($factory->keys as $key) {
            $this->$key = $factory->cache[$this->code][$key];
        }
        $this->_loaded = true;
    }
}
