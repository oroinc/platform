<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Cron;

use Oro\Bundle\ImapBundle\Mail\Protocol\Imap as Protocol;
use Oro\Bundle\ImapBundle\Mail\Storage\Imap;

class TestImap extends Imap
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        $this->messageClass = 'Oro\Bundle\ImapBundle\Mail\Storage\Message';
    }

    /**
     * @param Protocol $protocol
     */
    public function setProtocol(Protocol $protocol)
    {
        $this->protocol = $protocol;
    }
}
