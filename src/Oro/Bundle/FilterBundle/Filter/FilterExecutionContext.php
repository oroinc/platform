<?php

namespace Oro\Bundle\FilterBundle\Filter;

/**
 * Represents a context in which filters are applied.
 */
class FilterExecutionContext
{
    /** @var int */
    private $validationCounter = 0;

    /**
     * Enables using of forms to validate data of filters.
     */
    public function enableValidation(): void
    {
        $this->validationCounter++;
    }

    /**
     * Disables using of forms to validate data of filters.
     */
    public function disableValidation(): void
    {
        if (0 === $this->validationCounter) {
            throw new \LogicException('The validation is disabled by default.');
        }
        $this->validationCounter--;
    }

    /**
     * Indicates whether using of forms to validate data of filters is enabled.
     */
    public function isValidationEnabled(): bool
    {
        return $this->validationCounter > 0;
    }

    /**
     * Normalizes a filter data taking into account whether using of forms to validate data of filters
     * is enabled or not.
     *
     * @param FilterInterface $filter
     * @param mixed           $data
     *
     * @return mixed The normalized data or NULL if the validation is enabled and the data is not valid
     */
    public function normalizedFilterData(FilterInterface $filter, $data)
    {
        if (!$this->isValidationEnabled()) {
            return $filter->prepareData($data);
        }

        $form = $filter->getForm();
        if (!$form->isSubmitted()) {
            $form->submit($data);
        }

        if ($form->isValid()) {
            return $form->getData();
        }

        return null;
    }
}
