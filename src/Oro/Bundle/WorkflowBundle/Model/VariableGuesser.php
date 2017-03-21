<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Validator\Constraint;

use Oro\Bundle\ActionBundle\Model\AbstractGuesser;

class VariableGuesser extends AbstractGuesser
{
    const DEFAULT_CONSTRAINT_NAMESPACE = 'Symfony\\Component\\Validator\\Constraints\\';

    /**
     * @param Variable $variable
     *
     * @return null|TypeGuess
     */
    public function guessVariableForm(Variable $variable)
    {
        $type = $variable->getType();
        if ('entity' === $type) {
            list($formType, $formOptions) = $this->getEntityForm($variable);

            return new TypeGuess($formType, $formOptions, TypeGuess::VERY_HIGH_CONFIDENCE);
        }

        if (!isset($this->formTypeMapping[$type])) {
            return null;
        }

        $formType = $this->formTypeMapping[$type]['type'];
        $formOptions = array_merge_recursive($this->formTypeMapping[$type]['options'], $variable->getFormOptions());

        $formOptions = $this->setVariableFormOptions($variable, $formOptions);

        return new TypeGuess($formType, $formOptions, TypeGuess::VERY_HIGH_CONFIDENCE);
    }

    /**
     * @param Variable $variable
     * @param array    $formOptions
     *
     * @return array
     */
    protected function setVariableFormOptions(Variable $variable, $formOptions)
    {
        if (null !== $variable->getLabel()) {
            $formOptions['label'] = $variable->getLabel();
        }
        if (null !== $variable->getValue()) {
            $formOptions['data'] = $variable->getValue();
        }

        if (!isset($formOptions['constraints']) || !is_array($formOptions['constraints'])) {
            return $formOptions;
        }

        foreach ($formOptions['constraints'] as $constraint => $constraintOptions) {
            if ($constraintOptions instanceof Constraint) {
                continue;
            }

            unset($formOptions['constraints'][$constraint]);
            if (false === strpos($constraint, '\\')) {
                $constraint = sprintf('%s%s', self::DEFAULT_CONSTRAINT_NAMESPACE, $constraint);
            }
            $formOptions['constraints'][] = new $constraint($constraintOptions);
        }

        return $formOptions;
    }

    /**
     * @param Variable $variable
     *
     * @return array
     *
     */
    protected function getEntityForm(Variable $variable)
    {
        $entityClass = $variable->getOption('class');
        $formType = $variable->getOption('form_type');
        $formOptions = $variable->getOption('form_options', []);
        if ($this->formConfigProvider->hasConfig($entityClass) && !$formType) {
            $formConfig = $this->formConfigProvider->getConfig($entityClass);
            $formType = $formConfig->get('form_type');
            $formOptions = array_merge($formConfig->get('form_options', false, []), $formOptions);
        }
        if (!$formType) {
            $formType = 'entity';
            $formOptions = [
                'class' => $entityClass,
                'multiple' => false,
            ];
        }

        $formOptions = $this->setVariableFormOptions($variable, $formOptions);

        return [$formType, $formOptions];
    }
}
