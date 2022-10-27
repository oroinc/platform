<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Extends a Translator for debug purposes
 */
class DebugTranslator extends Translator
{
    public function trans(?string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return sprintf(
            $this->getStringFormat($id, $domain, $locale),
            parent::trans($id, $parameters, $domain, $locale)
        );
    }

    protected function getStringFormat(?string $id, ?string $domain = null, ?string $locale = null): string
    {
        if ($this->hasTrans($id, $domain, $locale)) {
            return '[%s]';
        }

        return '!!!---%s---!!!';
    }
}
