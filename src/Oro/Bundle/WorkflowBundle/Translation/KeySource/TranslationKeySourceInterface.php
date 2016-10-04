<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeySource;

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
