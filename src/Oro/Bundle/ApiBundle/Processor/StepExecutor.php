<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Model\Error;

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
     * Creates the execution context.
     *
     * @return ByStepNormalizeResultContext
     */
    public function createContext(): ByStepNormalizeResultContext
    {
        return $this->processor->createContext();
    }

    /**
     * Executes the given step.
     *
     * @param string                       $stepName    The step name to be executed
     * @param ByStepNormalizeResultContext $context     The execution context
     * @param bool                         $resetErrors Whether existing in the context errors
     *                                                  should be removed before the execution of the step
     *                                                  and then added to the context together with new errors, if any
     */
    public function executeStep(
        string $stepName,
        ByStepNormalizeResultContext $context,
        bool $resetErrors = true
    ): void {
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
     * @param Error[]                      $errors
     */
    private function saveErrors(ByStepNormalizeResultContext $context, array $errors): void
    {
        foreach ($errors as $error) {
            $context->addError($error);
        }
    }
}
