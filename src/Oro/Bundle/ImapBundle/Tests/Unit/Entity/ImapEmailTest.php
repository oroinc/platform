<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class ImapEmailTest extends TestCase
{
    public function testGetId(): void
    {
        $imapEmail = new ImapEmail();
        ReflectionUtil::setId($imapEmail, 123);
        $this->assertEquals(123, $imapEmail->getId());
    }

    public function testUidGetterAndSetter(): void
    {
        $imapEmail = new ImapEmail();
        $this->assertNull($imapEmail->getUid());
        $imapEmail->setUid(123);
        $this->assertEquals(123, $imapEmail->getUid());
    }

    public function testEmailGetterAndSetter(): void
    {
        $email = new Email();

        $imapEmail = new ImapEmail();
        $this->assertNull($imapEmail->getEmail());
        $imapEmail->setEmail($email);
        $this->assertSame($email, $imapEmail->getEmail());
    }

    public function testImapFolderGetterAndSetter(): void
    {
        $folder = new ImapEmailFolder();

        $imapEmail = new ImapEmail();
        $this->assertNull($imapEmail->getImapFolder());
        $imapEmail->setImapFolder($folder);
        $this->assertSame($folder, $imapEmail->getImapFolder());
    }
}
