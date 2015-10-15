<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\GuesserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @var array
     */
    protected $guessers;

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
     * @param array  $column
     *
     * @return array
     */
    public function getColumnOptions($columnName, $entityName, $column)
    {
        /** @var ValidatorMetadata $validatorMetadata */
        $validatorMetadata = $this->validator->getMetadataFor($entityName);

        foreach ($this->guessers as $guesser) {
            $options = $guesser->guessColumnOptions($columnName, $entityName, $column);

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
     * @param ValidatorMetadata $validatorMetadata
     * @param string            $columnName
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
