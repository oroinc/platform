<?php
namespace Oro\Component\Messaging\Transport;

interface Connection
{
    /**
     * @return Session
     */
    public function createSession();

    public function close();
}
