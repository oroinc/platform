<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator allows to checks whether the value is not less then the old one for ConfigType form item.
 */
class NotLessThanOriginalValueValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NotLessThanOriginalValue) {
            throw new UnexpectedTypeException($constraint, NotLessThanOriginalValue::class);
        }

        if (null === $value || null === $this->context->getObject()) {
            return;
        }

        $fieldConfigModel = $this->getFieldConfigModel();

        if (!$this->isValidatorShouldBeExecuted($fieldConfigModel, $constraint)) {
            return;
        }

        $scopeOptions = $fieldConfigModel->toArray($constraint->scope);
        $originalValue = $scopeOptions[$constraint->option];
        if ((int)$originalValue > (int)$value) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ originalValue }}', $originalValue)
                ->addViolation();
        }
    }

    private function isValidatorShouldBeExecuted(FieldConfigModel $model, Constraint $constraint): bool
    {
        // validator should be executed only during editing the field.
        $scopeOptions = $model->toArray($constraint->scope);
        if (null === $model->getId() || empty($scopeOptions) || empty($scopeOptions[$constraint->option])) {
            return false;
        }

        // validator should not be executed if the field is in New state
        $extendScopeOptions = $model->toArray('extend');
        if (!empty($extendScopeOptions)
            && !empty($extendScopeOptions['state'])
            && $extendScopeOptions['state'] === 'New'
        ) {
            return false;
        }

        return true;
    }

    private function getFieldConfigModel(): FieldConfigModel
    {
        /** @var FormInterface $form */
        $form = $this->context->getObject();
        if (!$form instanceof FormInterface) {
            throw new UnexpectedTypeException($form, FormInterface::class);
        }

        $rootForm = $this->getRootForm($form);
        $rootFormConfig = $rootForm->getConfig();

        if (!$rootFormConfig->hasOption('config_model')) {
            throw new \RuntimeException('Validator should be used only with ConfigType root form');
        }

        $fieldConfigModel = $rootFormConfig->getOption('config_model');
        if (!$fieldConfigModel instanceof FieldConfigModel) {
            throw new UnexpectedTypeException($fieldConfigModel, FieldConfigModel::class);
        }

        return $fieldConfigModel;
    }

    private function getRootForm(FormInterface $form): FormInterface
    {
        if ($form->isRoot()) {
            return $form;
        }

        return $this->getRootForm($form->getParent());
    }
}
