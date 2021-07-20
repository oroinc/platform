<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\GuesserInterface;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Guessing the column options for the Inline Editing extension
 */
class InlineEditColumnOptionsGuesser
{
    /** @var iterable|GuesserInterface[] */
    private $guessers;

    /** @var ValidatorInterface */
    private $validator;

    /** @var ConstraintConverterInterface */
    private $constraintConverter;

    /**
     * @param iterable|GuesserInterface[]  $guessers
     * @param ValidatorInterface           $validator
     * @param ConstraintConverterInterface $constraintConverter
     */
    public function __construct(
        iterable $guessers,
        ValidatorInterface $validator,
        ConstraintConverterInterface $constraintConverter
    ) {
        $this->guessers = $guessers;
        $this->validator = $validator;
        $this->constraintConverter = $constraintConverter;
    }

    /**
     * @param string $columnName
     * @param string $entityName
     * @param array  $column
     * @param string $behaviour
     *
     * @return array
     */
    public function getColumnOptions($columnName, $entityName, $column, $behaviour)
    {
        /** @var ClassMetadataInterface $validatorMetadata */
        $validatorMetadata = $this->validator->getMetadataFor($entityName);

        // The column option should always be prioritized than behaviour
        $isEnabledInline = $column[Configuration::BASE_CONFIG_KEY][Configuration::CONFIG_ENABLE_KEY]
            ?? ($behaviour === Configuration::BEHAVIOUR_ENABLE_ALL_VALUE);

        foreach ($this->guessers as $guesser) {
            $options = $guesser->guessColumnOptions(
                $columnName,
                $entityName,
                $column,
                $isEnabledInline
            );

            if (!empty($options)) {
                if ($validatorMetadata->hasPropertyMetadata($columnName)) {
                    $validationGroups = $column[Configuration::BASE_CONFIG_KEY]['validation_groups'] ?? [];

                    $options[Configuration::BASE_CONFIG_KEY]['validation_rules'] =
                        $this->getValidationRules($validatorMetadata, $columnName, $validationGroups);
                }

                return $options;
            }
        }

        return [];
    }

    /**
     * @param ClassMetadataInterface $validatorMetadata
     * @param string                 $columnName
     * @param string[]               $validationGroups
     *
     * @return array
     */
    private function getValidationRules(
        ClassMetadataInterface $validatorMetadata,
        string $columnName,
        array $validationGroups
    ) {
        /** @var PropertyMetadataInterface $metadata */
        $metadata = $validatorMetadata->getPropertyMetadata($columnName);
        $metadata = is_array($metadata) && isset($metadata[0]) ? $metadata[0] : $metadata;

        $rules = [];
        foreach ($metadata->getConstraints() as $constraint) {
            if ($validationGroups && !$this->isConstraintInGroups($constraint, $validationGroups)) {
                continue;
            }

            $jsConstraint = $this->constraintConverter->convertConstraint($constraint);
            if (null === $jsConstraint) {
                continue;
            }

            $ruleKey = $this->getRuleKey($jsConstraint);
            if (!isset($rules[$ruleKey])) {
                $rules[$ruleKey] = (array)$jsConstraint;
            } elseif (!$this->isDefaultConstraint($rules[$ruleKey])) {
                $rules[$ruleKey][] = $jsConstraint;
            }
        }

        return $rules;
    }

    /**
     * @param Constraint|array  $constraint
     * @param array             $expectedGroups
     *
     * @return bool
     */
    private function isConstraintInGroups($constraint, array $expectedGroups): bool
    {
        $groups = $this->getGroupsByConstraint($constraint);

        return (bool)array_intersect($groups, $expectedGroups);
    }

    /**
     * @param Constraint|array $constraint
     *
     * @return bool
     */
    private function isDefaultConstraint($constraint): bool
    {
        $groups = $this->getGroupsByConstraint($constraint);

        return in_array(Constraint::DEFAULT_GROUP, $groups, true);
    }

    /**
     * @param Constraint|array $constraint
     *
     * @return array
     */
    private function getGroupsByConstraint($constraint)
    {
        if (is_array($constraint)) {
            return $constraint['groups'] ?? [];
        }

        return $constraint->groups;
    }

    private function getRuleKey(Constraint $constraint): string
    {
        $reflectionClass = new \ReflectionClass($constraint);

        return $reflectionClass->getNamespaceName() === substr(AbstractLoader::DEFAULT_NAMESPACE, 1, -1)
            ? $reflectionClass->getShortName()
            : $reflectionClass->getName();
    }
}
