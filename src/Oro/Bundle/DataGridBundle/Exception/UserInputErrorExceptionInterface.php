<?php

namespace Oro\Bundle\DataGridBundle\Exception;

interface UserInputErrorExceptionInterface
{
    const TYPE = 'user_input_error';

    /**
     * Get error message translation key
     *
     * @return string
     */
    public function getMessageTemplate();

    /**
     * Get error  message translation params
     *
     * @return mixed
     */
    public function getMessageParams();
}
