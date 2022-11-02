<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\DTO;

/**
 * DTO for selected items for mass actions.
 */
class SelectedItems
{
    /**
     * @var array $values
     */
    private $values;

    /**
     * @var bool
     */
    private $inset;

    public function __construct(array $values, bool $inset)
    {
        $this->values = $values;
        $this->inset = $inset;
    }

    public function isEmpty(): bool
    {
        return $this->inset && empty($this->values);
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function isInset(): bool
    {
        return $this->inset;
    }

    /**
     * @param array $parameters
     * @return static
     */
    public static function createFromParameters(array $parameters)
    {
        $parameters = array_merge(
            [
                'inset' => true,
                'values' => []
            ],
            $parameters
        );

        return new static($parameters['values'], $parameters['inset']);
    }
}
