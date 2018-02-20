<?php

namespace Oro\Bundle\WorkflowBundle\Formatter;

use Oro\Bundle\WorkflowBundle\Model\Variable;
use Symfony\Component\Translation\TranslatorInterface;

class WorkflowVariableFormatter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Variable $workflowVariable
     * @return string
     */
    public function formatWorkflowVariableValue(Variable $workflowVariable): string
    {
        $value = $workflowVariable->getValue();

        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        }

        if (is_array($value)) {
            return implode(', ', $value);
        }

        if (in_array(strtolower($workflowVariable->getType()), ['bool', 'boolean'], true)) {
            $key =  $value ? 'Yes' : 'No';

            return $this->translator->trans($key);
        }

        return (string)$value;
    }
}
