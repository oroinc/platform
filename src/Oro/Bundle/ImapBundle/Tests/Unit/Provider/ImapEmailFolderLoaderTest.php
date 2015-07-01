<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Provider;

use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;

class ImapEmailFolderLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connector;

    /** @var ImapEmailFolderManager */
    protected $manager;

    protected function setUp()
    {
        $this->connector = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ImapEmailFolderManager($this->connector);
    }

    public function testLoadEmailFolders()
    {
        $folders = $this->manager->getFolders(null, true);
        $this->assertNull($folders);
    }

}

