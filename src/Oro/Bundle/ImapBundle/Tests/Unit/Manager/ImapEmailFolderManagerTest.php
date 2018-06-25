<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Oro\Bundle\ImapBundle\Mail\Storage\Folder;

class ImapEmailFolderManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $connector;

    protected function setUp()
    {
        $this->connector = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapConnector')
            ->disableOriginalConstructor()
            ->setMethods(array('findFolders'))
            ->getMock();
    }

    public function testGetFolders()
    {
        $inboxFolder         = $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']);
        $subFolder           = $this->createRemoteFolder('Inbox', '[Gmail]\Test');
        $sentFolder          = $this->createRemoteFolder('Sent', '[Gmail]\Sent', ['\Sent']);
        $spamFolder          = $this->createRemoteFolder('Spam', '[Gmail]\Spam', ['\Spam']);
        $trashFolder         = $this->createRemoteFolder('Spam', '[Gmail]\Trash', ['\Trash']);
        $nonSelectableFolder = $this->createRemoteFolder('All', 'All', [], false);

        $this->connector->expects($this->once())
            ->method('findFolders')
            ->with(null, true)
            ->will(
                $this->returnValue(
                    [
                        $inboxFolder,
                        $subFolder,
                        $sentFolder,
                        $spamFolder,
                        $trashFolder,
                        $nonSelectableFolder
                    ]
                )
            );

        $folders = $this->connector->findFolders(null, true);
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
