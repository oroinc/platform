<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Component\Testing\ReflectionUtil;

class ImapEmailFolderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetId(): void
    {
        $imapFolder = new ImapEmailFolder();
        ReflectionUtil::setId($imapFolder, 123);
        $this->assertEquals(123, $imapFolder->getId());
    }

    public function testUidValidityGetterAndSetter(): void
    {
        $imapFolder = new ImapEmailFolder();
        $this->assertNull($imapFolder->getUidValidity());
        $imapFolder->setUidValidity(123);
        $this->assertEquals(123, $imapFolder->getUidValidity());
    }

    public function testFolderGetterAndSetter(): void
    {
        $folder = new EmailFolder();

        $imapFolder = new ImapEmailFolder();
        $this->assertNull($imapFolder->getFolder());
        $imapFolder->setFolder($folder);
        $this->assertSame($folder, $imapFolder->getFolder());
    }

    public function testLastUidGetterEndSetter(): void
    {
        $imapFolder = new ImapEmailFolder();
        $this->assertNull($imapFolder->getLastUid());
        $imapFolder->setLastUid(145);
        $this->assertEquals(145, $imapFolder->getLastUid());
    }
}
