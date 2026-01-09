<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Error;

/**
 * Defines the contract for handling errors that occur during channel action execution.
 *
 * Implementations of this interface are responsible for processing and reporting errors
 * that arise when performing channel actions such as enable, disable, or delete operations.
 */
interface ChannelActionErrorHandlerInterface
{
    /**
     * @param string[] $errors
     */
    public function handleErrors($errors);
}
