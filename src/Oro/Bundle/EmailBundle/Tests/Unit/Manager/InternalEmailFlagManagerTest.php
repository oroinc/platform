<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Manager\InternalEmailFlagManager;

class InternalEmailFlagManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var InternalEmailFlagManager */
    private $flagManager;

    protected function setUp(): void
    {
        $this->flagManager = new InternalEmailFlagManager();
    }

    public function testSetSeen()
    {
        $emailFolder = $this->createMock(EmailFolder::class);
        $email = $this->createMock(Email::class);

        $this->flagManager->setSeen($emailFolder, $email);
    }

    public function testSetUnseen()
    {
        $emailFolder = $this->createMock(EmailFolder::class);
        $email = $this->createMock(Email::class);

        $this->flagManager->setUnseen($emailFolder, $email);
    }
}
