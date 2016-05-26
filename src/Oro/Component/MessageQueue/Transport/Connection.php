<?php
namespace Oro\Component\MessageQueue\Transport;

interface Connection
{
    /**
     * @return Session
     */
    public function createSession();

    public function close();
}
