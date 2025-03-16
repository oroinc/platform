<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Manager\InternalEmailFlagManager;
use PHPUnit\Framework\TestCase;

class InternalEmailFlagManagerTest extends TestCase
{
    private InternalEmailFlagManager $flagManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->flagManager = new InternalEmailFlagManager();
    }

    public function testSetSeen(): void
    {
        $emailFolder = $this->createMock(EmailFolder::class);
        $email = $this->createMock(Email::class);

        $this->flagManager->setSeen($emailFolder, $email);
    }

    public function testSetUnseen(): void
    {
        $emailFolder = $this->createMock(EmailFolder::class);
        $email = $this->createMock(Email::class);

        $this->flagManager->setUnseen($emailFolder, $email);
    }
}
