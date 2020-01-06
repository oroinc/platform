<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\GuesserInterface;
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

    /**
     * @param iterable|GuesserInterface[] $guessers
     * @param ValidatorInterface          $validator
     */
    public function __construct(iterable $guessers, ValidatorInterface $validator)
    {
        $this->guessers = $guessers;
        $this->validator = $validator;
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
                    $options[Configuration::BASE_CONFIG_KEY]['validation_rules'] =
                        $this->getValidationRules($validatorMetadata, $columnName);
                }

                return $options;
            }
        }

        return [];
    }

    /**
     * @param ClassMetadataInterface $validatorMetadata
     * @param string                 $columnName
     *
     * @return array
     */
    private function getValidationRules($validatorMetadata, $columnName)
    {
        /** @var PropertyMetadataInterface $metadata */
        $metadata = $validatorMetadata->getPropertyMetadata($columnName);
        $metadata = is_array($metadata) && isset($metadata[0]) ? $metadata[0] : $metadata;

        $rules = [];
        foreach ($metadata->getConstraints() as $constraint) {
            $reflectionClass = new \ReflectionClass($constraint);
            $ruleKey = $reflectionClass->getNamespaceName() === substr(AbstractLoader::DEFAULT_NAMESPACE, 1, -1)
                ? $reflectionClass->getShortName()
                : $reflectionClass->getName();
            if (!isset($rules[$ruleKey])) {
                $rules[$ruleKey] = (array)$constraint;
            } elseif (!$this->isDefaultConstraint($rules[$ruleKey])) {
                $rules[$ruleKey][] = $constraint;
            }
        }

        return $rules;
    }

    /**
     * @param Constraint|array $constraint
     *
     * @return bool
     */
    private function isDefaultConstraint($constraint)
    {
        $groups = is_array($constraint) ? $constraint['groups'] : $constraint->groups;

        return in_array(Constraint::DEFAULT_GROUP, $groups, true);
    }
}
