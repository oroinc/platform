<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailFolderManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;

class EmailFolderManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailFolderManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $selector;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    protected function setUp()
    {
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->selector = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailFolderLoaderSelector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailFolderManager($this->selector);
        $this->manager->setLogger($this->logger);
    }

    public function testGetEmailFolders()
    {
        $origin = new TestEmailOrigin();
        $inboxFolder = $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']);

        $loader = $this->getMock('Oro\Bundle\EmailBundle\Provider\EmailFolderLoaderInterface');

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($loader));
        $loader->expects($this->once())
            ->method('loadEmailFolders')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue(array($inboxFolder)));

        $folders = $this->manager->getEmailFolders($origin);

        $this->logger->expects($this->never())
            ->method('notice');
        $this->logger->expects($this->never())
            ->method('warning');

        $this->assertEquals($inboxFolder, $folders[0]);
    }

    /**
     * @param string $localName
     * @param string $globalName
     * @param array  $flags
     * @param bool   $selectable
     *
     * @return Folder
     */
    protected function createRemoteFolder($localName, $globalName, array $flags = [], $selectable = true)
    {
        $folder = new Folder($localName, $globalName, $selectable);
        $folder->setFlags($flags);

        return $folder;
    }
}
