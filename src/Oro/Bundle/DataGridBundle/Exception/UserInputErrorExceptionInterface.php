<?php

namespace Oro\Bundle\DataGridBundle\Exception;

/**
 * Datagrid Exception for user input error
 */
interface UserInputErrorExceptionInterface extends DatagridException
{
    public const TYPE = 'user_input_error';

    /**
     * Get error message translation key
     *
     * @return string
     */
    public function getMessageTemplate(): string;

    /**
     * Get error  message translation params
     *
     * @return array
     */
    public function getMessageParams(): array;
}
