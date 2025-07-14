<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\TestFixtures;

use Oro\Bundle\ImapBundle\Mail\Storage\Imap;

class Imap2 extends Imap
{
    public function __construct($params)
    {
    }

    #[\Override]
    public function __destruct()
    {
    }

    #[\Override]
    public function capability()
    {
        return ['FEATURE1', 'FEATURE2'];
    }
}
