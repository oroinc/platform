<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;

/**
 * Provides constraints for a given form.
 */
class ConstraintsProvider implements ConstraintsProviderInterface
{
    /** @var MetadataFactoryInterface */
    private $metadataFactory;

    /** @var ConstraintConverterInterface */
    private $constraintConverter;

    /** @var array */
    private $metadataConstraintsCache;

    public function __construct(
        MetadataFactoryInterface $metadataFactory,
        ConstraintConverterInterface $constraintConverter
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->constraintConverter = $constraintConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormConstraints(FormInterface $form)
    {
        $constraints = $this->getMetadataConstraints($form);

        $embeddedConstraints = $form->getConfig()->getOption('constraints');
        if ($embeddedConstraints && is_array($embeddedConstraints)) {
            $constraints = array_merge($constraints, $embeddedConstraints);
        }

        $validationGroups = $this->getValidationGroups($form);

        $result = [];
        foreach ($constraints as $constraint) {
            $groups = $constraint->groups ?? [Constraint::DEFAULT_GROUP];
            if (array_intersect($validationGroups, $groups)) {
                $jsConstraint = $this->constraintConverter->convertConstraint($constraint);
                if (null !== $jsConstraint) {
                    $result[] = $jsConstraint;
                }
            }
        }

        return $result;
    }

    /**
     * Gets constraints for form view based on metadata
     *
     * @param FormInterface $form
     * @return array
     */
    protected function getMetadataConstraints(FormInterface $form)
    {
        $isMapped = $form->getConfig()->getOption('mapped', true);

        if (!$form->getParent() || !$isMapped) {
            return [];
        }

        $name = $form->getName();
        $parent = $form->getParent();
        $parentKey = spl_object_hash($parent);

        if (!isset($this->metadataConstraintsCache[$parentKey])) {
            $this->metadataConstraintsCache[$parentKey] = $this->extractMetadataPropertiesConstraints($parent);
        }

        $result = [];
        if (isset($this->metadataConstraintsCache[$parentKey][$name])) {
            $result = $this->metadataConstraintsCache[$parentKey][$name]->constraints;
        } else {
            //If no metadata for the fields name, try getting it with the property path
            $propertyPath = (string)$form->getPropertyPath();
            if (isset($this->metadataConstraintsCache[$parentKey][$propertyPath])) {
                $result = $this->metadataConstraintsCache[$parentKey][$propertyPath]->constraints;
            }
        }

        return $result;
    }

    /**
     * Extracts constraints based on validation metadata
     *
     * @param FormInterface $form
     * @return array
     */
    protected function extractMetadataPropertiesConstraints(FormInterface $form)
    {
        $constraints = [];
        $dataClass = $form->getConfig()->getDataClass() ?: $form->getConfig()->getOption('entity_class');
        if ($dataClass) {
            /** @var ClassMetadata $metadata */
            $metadata = $this->metadataFactory->getMetadataFor($dataClass);
            $constraints = $metadata->properties;
        }
        $errorMapping = $form->getConfig()->getOption('error_mapping');
        if (!empty($constraints) && !empty($errorMapping)) {
            foreach ($errorMapping as $originalName => $mappedName) {
                if (isset($constraints[$originalName])) {
                    $constraints[$mappedName] = $constraints[$originalName];
                }
            }
        }

        return $constraints;
    }

    /**
     * Returns the validation groups of the given form.
     *
     * @param FormInterface $form
     * @return array
     */
    protected function getValidationGroups(FormInterface $form)
    {
        do {
            $groups = $form->getConfig()->getOption('validation_groups');

            if (null !== $groups) {
                return $this->resolveValidationGroups($groups, $form);
            }

            $form = $form->getParent();
        } while (null !== $form);

        return [Constraint::DEFAULT_GROUP];
    }

    /**
     * Post-processes the validation groups option for a given form.
     *
     * @param array|callable $groups The validation groups.
     * @param FormInterface  $form   The validated form.
     *
     * @return array The validation groups.
     */
    protected function resolveValidationGroups($groups, FormInterface $form)
    {
        if (!is_string($groups) && is_callable($groups)) {
            $groups = $groups($form);
        }

        if ($groups instanceof GroupSequence) {
            $groups = (array) $groups->groups;
        }

        return (array) $groups;
    }
}
