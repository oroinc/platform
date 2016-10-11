<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

class WorkflowTemplate implements TranslationKeyTemplateInterface
{
    const KEY_PREFIX = 'oro.workflow';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return self::KEY_PREFIX . '.{{ workflow_name }}';
    }

    /**
     * @return array
     */
    public function getRequiredKeys()
    {
        return ['workflow_name'];
    }
}
