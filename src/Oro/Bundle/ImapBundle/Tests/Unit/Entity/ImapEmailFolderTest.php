<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;

class ImapEmailFolderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetId()
    {
        $imapFolder = new ImapEmailFolder();
        ReflectionUtil::setId($imapFolder, 123);
        $this->assertEquals(123, $imapFolder->getId());
    }

    public function testUidValidityGetterAndSetter()
    {
        $imapFolder = new ImapEmailFolder();
        $this->assertNull($imapFolder->getUidValidity());
        $imapFolder->setUidValidity(123);
        $this->assertEquals(123, $imapFolder->getUidValidity());
    }

    public function testFolderGetterAndSetter()
    {
        $folder = new EmailFolder();

        $imapFolder = new ImapEmailFolder();
        $this->assertNull($imapFolder->getFolder());
        $imapFolder->setFolder($folder);
        $this->assertSame($folder, $imapFolder->getFolder());
    }
}
