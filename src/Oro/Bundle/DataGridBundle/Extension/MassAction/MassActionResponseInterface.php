<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

/**
 * Defines the contract for mass action responses.
 *
 * This interface standardizes the response format from mass action handlers, providing
 * success status, user-facing messages, and optional additional data for frontend processing.
 */
interface MassActionResponseInterface
{
    /**
     * @return boolean
     */
    public function isSuccessful();

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name);
}
