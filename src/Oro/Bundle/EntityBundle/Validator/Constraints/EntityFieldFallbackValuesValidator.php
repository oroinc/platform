<?php

namespace Oro\Bundle\EntityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntityFieldFallbackValuesValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_field_fallback_values_validator';
    const SELECTED_VALUE = 'scalarValue';

    /**
     * @param $value
     * @param Constraint
     *
     * {@inheritdoc}
     */
    public function validate($entityFieldFallbackValue, Constraint $constraint)
    {
        $fieldName = 'pageTemplate'; // TODO - get this

        $selectedValues = $entityFieldFallbackValue->getOwnValue();
        $choices = $this->getFieldChoices($fieldName);

        if (!$this->selectedValuesAreValid($selectedValues, $choices)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    /**
     * Check if the selected value in the form was in the allowed choices list
     *
     * @param $selectedValues
     * @param $choices
     * @return bool
     */
    private function selectedValuesAreValid($selectedValues, $choices)
    {
        if (!is_array($selectedValues)) {
            $selectedValues = [$selectedValues];
        }

        foreach ($selectedValues as $selectedValue) {

            /* values are saved as keys in the form. ex:
             choices = [
              "short" => 1,
              "two-columns" => 2
            ]
            */
            if (!array_key_exists($selectedValue, $choices)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Based on the give fieldName retrieve the initial choices array
     * that is set in the built form
     *
     * @param $fieldName
     * @return array|void
     */
    private function getFieldChoices($fieldName)
    {
        $fieldFormType = $this->context->getRoot()->get($fieldName);

        if (empty($fieldFormType) || !$fieldFormType->has(self::SELECTED_VALUE)) {
            return;
        }

        return $fieldFormType->get(self::SELECTED_VALUE)->getConfig()->getOption('choices');
    }
}
