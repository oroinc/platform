<?php
namespace Oro\Component\MessageQueue\Transport;

interface ConnectionInterface
{
    /**
     * @return SessionInterface
     */
    public function createSession();

    public function close();
}
