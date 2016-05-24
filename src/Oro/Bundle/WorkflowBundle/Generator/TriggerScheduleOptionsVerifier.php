<?php

namespace Oro\Bundle\WorkflowBundle\Generator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class TriggerScheduleOptionsVerifier
{
    /** @var array */
    private $optionVerifiers = [];


    /**
     * @param array $options
     * @param WorkflowDefinition $workflowDefinition
     * @param string $transitionName
     */
    public function verify(array $options, WorkflowDefinition $workflowDefinition, $transitionName)
    {
        $this->verifyOptions($options);

        foreach ($this->optionVerifiers as $optionName => $optionVerifiers) {
            foreach ($optionVerifiers as $verifier) {
                /** @var ExpressionVerifierInterface $verifier */
                $verifier->verify($options[$optionName]);
            }
        }
    }

    /**
     * @param ExpressionVerifierInterface $verifier
     * @param string $option
     */
    public function addOptionVerifier($option, ExpressionVerifierInterface $verifier)
    {
        if (!array_key_exists($option, $this->optionVerifiers)) {
            $this->optionVerifiers[$option] = [];
        }

        $this->optionVerifiers[] = $verifier;
    }

    private function verifyOptions($expression)
    {
        if (!is_array($expression) || $expression instanceof \ArrayAccess) {
            throw new \InvalidArgumentException(
                'Schedule options must be an array or implement interface \ArrayAccess'
            );
        }

        if (!isset($expression['cron'])) {
            throw new \InvalidArgumentException(
                'Option "cron" is REQUIRED for transition schedule.'
            );
        }
    }
}
