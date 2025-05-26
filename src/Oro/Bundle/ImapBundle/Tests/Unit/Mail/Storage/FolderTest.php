<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use PHPUnit\Framework\TestCase;

class FolderTest extends TestCase
{
    public function testGuessStandartFolderType(): void
    {
        $folder = new Folder('');
        $folder->setFlags(['\\Inbox']);
        $this->assertEquals('inbox', $folder->guessFolderType());
    }

    public function testGuessKnownFolderType(): void
    {
        $folder = new Folder('Sent Items');
        $this->assertEquals('sent', $folder->guessFolderType());
    }

    public function testGuessUnknownFolderType(): void
    {
        $folder = new Folder('Unknown Folder');
        $this->assertEquals('other', $folder->guessFolderType());
    }
}
