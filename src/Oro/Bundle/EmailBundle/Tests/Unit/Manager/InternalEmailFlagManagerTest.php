<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Manager\InternalEmailFlagManager;

class InternalEmailFlagManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var InternalEmailFlagManager */
    private $flagManager;

    protected function setUp()
    {
        $this->flagManager = new InternalEmailFlagManager();
    }

    public function testSetSeen()
    {
        /** @var EmailFolder|\PHPUnit\Framework\MockObject\MockObject $emailFolder */
        $emailFolder = $this->createMock(EmailFolder::class);
        /** @var Email|\PHPUnit\Framework\MockObject\MockObject $email */
        $email = $this->createMock(Email::class);

        $this->assertNull($this->flagManager->setSeen($emailFolder, $email));
    }

    public function testSetUnseen()
    {
        /** @var EmailFolder|\PHPUnit\Framework\MockObject\MockObject $emailFolder */
        $emailFolder = $this->createMock(EmailFolder::class);
        /** @var Email|\PHPUnit\Framework\MockObject\MockObject $email */
        $email = $this->createMock(Email::class);

        $this->assertNull($this->flagManager->setUnseen($emailFolder, $email));
    }
}
