<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

interface TranslationKeyTemplateInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getTemplate();

    /**
     * @return array
     */
    public function getRequiredKeys();

    /**
     * @param string key
     * @return string
     */
    public function getKeyTemplate($key);

    /**
     * @return array
     */
    public function getKeyTemplates();
}
