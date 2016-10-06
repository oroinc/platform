<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

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
