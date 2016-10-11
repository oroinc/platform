<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

class WorkflowTemplate implements TranslationKeyTemplateInterface
{
    const NAME = 'workflow';
    const KEY_PREFIX = 'oro.workflow';

    /**
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

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

    /**
     * @return array
     */
    public function getKeyTemplates()
    {
        $result = [];
        foreach($this->getRequiredKeys() as $key) {
            $result[$key] = $this->getKeyTemplate($key);
        }

        return $result;
    }

    /**
     * @param string $attributeName
     * @return string
     */
    public function getKeyTemplate($attributeName)
    {
        return sprintf('{{ %s }}', $attributeName);
    }
}
