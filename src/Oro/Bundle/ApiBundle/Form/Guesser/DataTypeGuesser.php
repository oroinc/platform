<?php

namespace Oro\Bundle\ApiBundle\Form\Guesser;

use Oro\Bundle\ApiBundle\Request\DataType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Guesses form types based on "form_type_guesses" configuration.
 */
class DataTypeGuesser
{
    /** @var array [data type => [form type, options], ...] */
    private array $dataTypeMappings;

    /**
     * @param array $dataTypeMappings [data type => [form type, options], ...]
     */
    public function __construct(array $dataTypeMappings)
    {
        $this->dataTypeMappings = $dataTypeMappings;
    }

    /**
     * Returns a field guess for the given data type.
     */
    public function guessType(string $dataType): TypeGuess
    {
        if (isset($this->dataTypeMappings[$dataType])) {
            return $this->getTypeGuessForMappedDataType($dataType);
        }

        if (isset($this->dataTypeMappings[DataType::ARRAY]) && DataType::isArray($dataType)) {
            return $this->getTypeGuessForMappedDataType(DataType::ARRAY);
        }

        return $this->guessDefault();
    }

    /**
     * Returns a default field guess.
     */
    public function guessDefault(): TypeGuess
    {
        return new TypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE);
    }

    private function getTypeGuessForMappedDataType(string $dataType): TypeGuess
    {
        [$formType, $options] = $this->dataTypeMappings[$dataType];

        return new TypeGuess($formType, $options, TypeGuess::HIGH_CONFIDENCE);
    }
}
