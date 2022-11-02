<?php

namespace Oro\Bundle\FilterBundle\Form\EventListener;

/**
 * Provides a way to copy values from submitted data to model data.
 * It is required to correct work of date interval filters, e.g. the "day without year" variable.
 */
class DateFilterSubmitContext
{
    /** @var array|null */
    private $submittedData;

    /**
     * Adds a submitted value to the context.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function addValue(string $key, $value): void
    {
        $this->submittedData[$key] = $value;
    }

    /**
     * Adds all values from the context to model data and clears the context.
     */
    public function applyValues(array $data): array
    {
        if ($this->submittedData) {
            foreach ($this->submittedData as $key => $value) {
                $data['value'][$key] = $value;
            }
            $this->submittedData = null;
        }

        return $data;
    }
}
