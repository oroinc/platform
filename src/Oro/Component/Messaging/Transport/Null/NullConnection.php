<?php
namespace Oro\Component\Messaging\Transport\Null;

use Oro\Component\Messaging\Transport\Connection;

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
}
