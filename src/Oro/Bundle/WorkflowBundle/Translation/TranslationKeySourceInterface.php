<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

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
