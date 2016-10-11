<?php

namespace Oro\Bundle\TranslationBundle\Translation;

interface TranslationKeyTemplateInterface
{
    /**
     * @return string
     */
    public function getTemplate();

    /**
     * @return array
     */
    public function getRequiredKeys();
}
