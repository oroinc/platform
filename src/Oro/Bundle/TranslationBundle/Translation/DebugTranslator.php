<?php

namespace Oro\Bundle\TranslationBundle\Translation;

class DebugTranslator extends Translator
{
    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return sprintf(
            $this->getStringFormat($id, $domain, $locale),
            parent::trans($id, $parameters, $domain, $locale)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        return sprintf(
            $this->getStringFormat($id, $domain, $locale),
            parent::transChoice($id, $number, $parameters, $domain, $locale)
        );
    }

    /**
     * @param string $id
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    protected function getStringFormat($id, $domain = null, $locale = null)
    {
        if ($this->hasTrans($id, $domain, $locale)) {
            return '[%s]';
        } else {
            return '!!!---%s---!!!';
        }
    }
}
