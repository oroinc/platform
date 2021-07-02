<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;

class ImapEmailFolderManagerTest extends \PHPUnit\Framework\TestCase
{
    private function createRemoteFolder(
        string $localName,
        string $globalName,
        array $flags = [],
        bool $selectable = true
    ): Folder {
        $folder = new Folder($localName, $globalName, $selectable);
        $folder->setFlags($flags);

        return $folder;
    }

    public function testGetFolders()
    {
        $inboxFolder = $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']);
        $subFolder = $this->createRemoteFolder('Inbox', '[Gmail]\Test');
        $sentFolder = $this->createRemoteFolder('Sent', '[Gmail]\Sent', ['\Sent']);
        $spamFolder = $this->createRemoteFolder('Spam', '[Gmail]\Spam', ['\Spam']);
        $trashFolder = $this->createRemoteFolder('Spam', '[Gmail]\Trash', ['\Trash']);
        $nonSelectableFolder = $this->createRemoteFolder('All', 'All', [], false);

        $connector = $this->createMock(ImapConnector::class);
        $connector->expects($this->once())
            ->method('findFolders')
            ->with(null, true)
            ->willReturn([
                $inboxFolder,
                $subFolder,
                $sentFolder,
                $spamFolder,
                $trashFolder,
                $nonSelectableFolder
            ]);

        $folders = $connector->findFolders(null, true);
        $this->assertEquals($inboxFolder, $folders[0]);
    }
}
