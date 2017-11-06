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

    /**
     * @param array $values
     * @param bool $inset
     */
    public function __construct(array $values, bool $inset)
    {
        $this->values = $values;
        $this->inset = $inset;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->inset && empty($this->values);
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return bool
     */
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
