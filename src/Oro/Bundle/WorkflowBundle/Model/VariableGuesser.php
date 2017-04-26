<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\Form\Guess\TypeGuess;

class VariableGuesser
{
    /**
     * @var array
     */
    protected $formTypeMapping = [];

    /**
     * @param string $variableType
     * @param string $formType
     * @param array $formOptions
     */
    public function addFormTypeMapping($variableType, $formType, array $formOptions = [])
    {
        $this->formTypeMapping[$variableType] = [
            'type' => $formType,
            'options' => $formOptions,
        ];
    }

    /**
     * @param Variable $variable
     * @return null|TypeGuess
     */
    public function guessVariableForm(Variable $variable)
    {
        $type = $variable->getType();
        if (!isset($this->formTypeMapping[$type])) {
            return null;
        }

        $formType = $this->formTypeMapping[$type]['type'];
        $formOptions = array_merge_recursive($this->formTypeMapping[$type]['options'], $variable->getFormOptions());
        if (!is_null($variable->getLabel())) {
            $formOptions['label'] = $variable->getLabel();
        }
        if (!is_null($variable->getValue())) {
            $formOptions['data'] = $variable->getValue();
        }

        return new TypeGuess($formType, $formOptions, TypeGuess::VERY_HIGH_CONFIDENCE);
    }
}
