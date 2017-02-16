<?php

namespace Oro\Bundle\IntegrationBundle\Error\ActionHandler;

interface ChannelActionErrorHandlerInterface
{
    /**
     * @param string[] $errors
     */
    public function handleErrors($errors);
}
