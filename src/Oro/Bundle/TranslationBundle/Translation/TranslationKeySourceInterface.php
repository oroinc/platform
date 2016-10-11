<?php

namespace Oro\Bundle\TranslationBundle\Translation;

interface TranslationKeySourceInterface
{
    /**
     * @return string
     */
    public function getTemplate();

    /**
     * @return array
     */
    public function getData();
}
