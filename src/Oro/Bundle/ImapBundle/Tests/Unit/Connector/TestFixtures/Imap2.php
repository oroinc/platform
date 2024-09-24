<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\TestFixtures;

class Imap2 extends \Oro\Bundle\ImapBundle\Mail\Storage\Imap
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
