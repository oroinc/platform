<?php

namespace Oro\Bundle\WorkflowBundle\Formatter;

use Oro\Bundle\WorkflowBundle\Model\Variable;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowVariableFormatter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
