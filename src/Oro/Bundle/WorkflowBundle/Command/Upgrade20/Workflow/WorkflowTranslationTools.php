<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20\Workflow;

use Oro\Bundle\TranslationBundle\Translation\KeySource\DynamicTranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;

class WorkflowTranslationTools
{
    /** @var TranslationKeyGenerator */
    private $keyGenerator;

    /** @var DynamicTranslationKeySource */
    private $dynamicKeySource;

    /** @var array */
    private $templates = [];

    /** @return TranslationKeyGenerator */
    private function getKeyGenerator()
    {
        return $this->keyGenerator ?: $this->keyGenerator = new TranslationKeyGenerator();
    }

    /**
     * @return DynamicTranslationKeySource
     */
    private function getDynamicKeySource()
    {
        return $this->dynamicKeySource ?: $this->dynamicKeySource = new DynamicTranslationKeySource();
    }

    /**
     * @param array $data
     * @return string
     */
    public function workflowLabelKey(array $data)
    {
        return $this->key(WorkflowLabelTemplate::class, $data);
    }

    /**
     * @param array $data
     * @return string
     */
    public function stepLabelKey(array $data)
    {
        return $this->key(StepLabelTemplate::class, $data);
    }

    /**
     * @param array $data
     * @return string
     */
    public function attributeLabelKey(array $data)
    {
        return $this->key(WorkflowAttributeLabelTemplate::class, $data);
    }

    /**
     * @param array $data
     * @return string
     */
    public function transitionLabelKey(array $data)
    {
        return $this->key(TransitionLabelTemplate::class, $data);
    }

    /**
     * @param array $data
     * @return string
     */
    public function transitionMessageKey(array $data)
    {
        return $this->key(TransitionWarningMessageTemplate::class, $data);
    }

    /**
     * @param array $data
     * @return string
     */
    public function transitionAttributeLabelKey(array $data)
    {
        return $this->key(TransitionAttributeLabelTemplate::class, $data);
    }

    /**
     * @param string $templateClass
     * @param array $data
     * @return string
     */
    private function key($templateClass, array $data)
    {
        return $this->getKeyGenerator()->generate(
            $this->getDynamicKeySource()->configure(
                $this->getTemplateInstance($templateClass),
                $data
            )
        );
    }

    /**
     * @param string $class
     * @return TranslationKeyTemplateInterface
     * @throws \InvalidArgumentException
     */
    private function getTemplateInstance($class)
    {
        if (isset($this->templates[$class])) {
            return $this->templates[$class];
        }

        $template = new $class;

        if ($template instanceof TranslationKeyTemplateInterface) {
            return $this->templates[$class] = $template;
        }

        throw new \InvalidArgumentException(
            sprintf('need that implements %s got %s', TranslationKeyTemplateInterface::class, $class)
        );
    }
}
