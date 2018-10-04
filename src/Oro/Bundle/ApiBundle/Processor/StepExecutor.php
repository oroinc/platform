<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * A helper class that can be used to execute processors from a specified group.
 */
class StepExecutor
{
    /** @var ByStepNormalizeResultActionProcessor */
    private $processor;

    /**
     * @param ByStepNormalizeResultActionProcessor $processor
     */
    public function __construct(ByStepNormalizeResultActionProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @return ByStepNormalizeResultContext
     */
    public function createContext(): ByStepNormalizeResultContext
    {
        return $this->processor->createContext();
    }

    /**
     * @param string                       $stepName
     * @param ByStepNormalizeResultContext $context
     * @param bool                         $resetErrors
     */
    public function executeStep(string $stepName, ByStepNormalizeResultContext $context, $resetErrors = true): void
    {
        $context->setFirstGroup($stepName);
        $context->setLastGroup($stepName);

        if ($resetErrors) {
            $existingErrors = $context->getErrors();
            $context->resetErrors();
            try {
                $this->processor->process($context);
            } finally {
                if (!empty($existingErrors)) {
                    $newErrors = $context->getErrors();
                    $context->resetErrors();
                    $this->saveErrors($context, $existingErrors);
                    if (!empty($newErrors)) {
                        $this->saveErrors($context, $newErrors);
                    }
                }
            }
        } else {
            $this->processor->process($context);
        }
    }

    /**
     * @param ByStepNormalizeResultContext $context
     * @param array                        $errors
     */
    private function saveErrors(ByStepNormalizeResultContext $context, array $errors): void
    {
        foreach ($errors as $error) {
            $context->addError($error);
        }
    }
}
