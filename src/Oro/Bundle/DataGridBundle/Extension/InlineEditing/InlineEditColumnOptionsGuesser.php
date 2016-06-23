<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\GuesserInterface;

/**
 * Class InlineEditColumnOptionsGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing
 */
class InlineEditColumnOptionsGuesser
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var GuesserInterface[]
     */
    protected $guessers;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->guessers = [];
        $this->validator = $validator;
    }

    /**
     * @param GuesserInterface $guesser
     */
    public function addGuesser(GuesserInterface $guesser)
    {
        $this->guessers[] = $guesser;
    }

    /**
     * @param string $columnName
     * @param string $entityName
     * @param array $column
     * @param string $behaviour
     *
     * @return array
     */
    public function getColumnOptions($columnName, $entityName, $column, $behaviour)
    {
        /** @var ValidatorInterface $validatorMetadata */
        $validatorMetadata = $this->validator->getMetadataFor($entityName);
        $isEnabledInline =
            isset($column[Configuration::BASE_CONFIG_KEY][Configuration::CONFIG_ENABLE_KEY]) &&
            $column[Configuration::BASE_CONFIG_KEY][Configuration::CONFIG_ENABLE_KEY] === true;

        if ($behaviour === Configuration::BEHAVIOUR_ENABLE_ALL_VALUE ||
            ($behaviour === Configuration::BEHAVIOUR_ENABLE_SELECTED && $isEnabledInline)) {
            $isEnabledInlineWithBehaviour = true;
        } else {
            $isEnabledInlineWithBehaviour = false;
        }

        foreach ($this->guessers as $guesser) {
            $options = $guesser->guessColumnOptions(
                $columnName,
                $entityName,
                $column,
                $isEnabledInlineWithBehaviour
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
     * @param string $columnName
     *
     * @return array
     */
    protected function getValidationRules($validatorMetadata, $columnName)
    {
        $metadata = $validatorMetadata->getPropertyMetadata($columnName);
        $metadata = is_array($metadata) && isset($metadata[0]) ? $metadata[0] : $metadata;

        $rules = [];
        foreach ($metadata->getConstraints() as $constraint) {
            $reflectionClass = new \ReflectionClass($constraint);
            $rules[$reflectionClass->getShortName()] = (array)$constraint;
        }

        return $rules;
    }
}
