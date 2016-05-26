<?php
namespace Oro\Component\MessageQueue\Transport\Null;

use Oro\Component\MessageQueue\Transport\Connection;

class NullConnection implements Connection
{
    /**
     * {@inheritdoc}
     *
     * @return NullSession
     */
    public function createSession()
    {
        return new NullSession();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }
}
