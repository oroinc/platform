<?php

namespace Oro\Bundle\UIBundle\Tools\HTMLPurifier;

use Oro\Bundle\TranslationBundle\Translation\TranslatorAwareInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslatorAwareTrait;

/**
 * Class responsible for generating Language objects, managing
 * caching and fallbacks.
 */
class LanguageFactory extends \HTMLPurifier_LanguageFactory implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * {@inheritDoc}
     * @return LanguageFactory
     */
    public static function instance($prototype = null)
    {
        $instance = null;
        if ($prototype !== null && $prototype !== true) {
            $instance = $prototype;
        } elseif ($instance === null || $prototype === true) {
            $instance = new self();
            $instance->setup();
        }

        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function create($config, $context, $code = false)
    {
        $language = new Language($config, $context);
        $language->setTranslator($this->translator);

        return $language;
    }

    /**
     * {@inheritDoc}
     */
    public function loadLanguage($code)
    {
        parent::loadLanguage($code);

        if ($this->translator) {
            foreach ($this->cache[$code]['messages'] as $key => $value) {
                $this->cache[$code]['messages'][$key] = $this->translator->trans('oro.htmlpurifier.messages.' . $key);
            }
        }
    }
}
