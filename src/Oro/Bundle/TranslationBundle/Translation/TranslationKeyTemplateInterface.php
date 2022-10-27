<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Common interface for translation templates.
 */
interface TranslationKeyTemplateInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getTemplate(): string;

    /**
     * @return array
     */
    public function getRequiredKeys();

    /**
     * @param string $key
     * @return string
     */
    public function getKeyTemplate($key);

    /**
     * @return array
     */
    public function getKeyTemplates();
}
