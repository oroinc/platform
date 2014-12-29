<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;

class ImapEmailTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $imapEmail = new ImapEmail();
        ReflectionUtil::setId($imapEmail, 123);
        $this->assertEquals(123, $imapEmail->getId());
    }

    public function testUidGetterAndSetter()
    {
        $imapEmail = new ImapEmail();
        $this->assertNull($imapEmail->getUid());
        $imapEmail->setUid(123);
        $this->assertEquals(123, $imapEmail->getUid());
    }

    public function testEmailGetterAndSetter()
    {
        $email = new Email();

        $imapEmail = new ImapEmail();
        $this->assertNull($imapEmail->getEmail());
        $imapEmail->setEmail($email);
        $this->assertSame($email, $imapEmail->getEmail());
    }

    public function testImapFolderGetterAndSetter()
    {
        $folder = new ImapEmailFolder();

        $imapEmail = new ImapEmail();
        $this->assertNull($imapEmail->getImapFolder());
        $imapEmail->setImapFolder($folder);
        $this->assertSame($folder, $imapEmail->getImapFolder());
    }
}
