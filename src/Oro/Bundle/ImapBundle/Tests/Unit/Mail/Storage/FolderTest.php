<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Oro\Bundle\ImapBundle\Mail\Storage\Folder;

class FolderTest extends \PHPUnit\Framework\TestCase
{
    public function testGuessStandartFolderType()
    {
        $folder = new Folder('');
        $folder->setFlags(['\\Inbox']);
        $this->assertEquals('inbox', $folder->guessFolderType());
    }

    public function testGuessKnownFolderType()
    {
        $folder = new Folder('Sent Items');
        $this->assertEquals('sent', $folder->guessFolderType());
    }

    public function testGuessUnknownFolderType()
    {
        $folder = new Folder('Unknown Folder');
        $this->assertEquals('other', $folder->guessFolderType());
    }
}
