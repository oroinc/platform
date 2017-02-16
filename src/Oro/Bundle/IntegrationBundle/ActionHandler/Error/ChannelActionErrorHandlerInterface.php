<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Error;

interface ChannelActionErrorHandlerInterface
{
    /**
     * @param string[] $errors
     */
    public function handleErrors($errors);
}
