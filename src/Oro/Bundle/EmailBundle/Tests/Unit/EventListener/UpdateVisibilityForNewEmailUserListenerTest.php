<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Event\EmailUserAdded;
use Oro\Bundle\EmailBundle\EventListener\UpdateVisibilityForNewEmailUserListener;

class UpdateVisibilityForNewEmailUserListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testUpdateVisibilityForEmailUser()
    {
        $emailUser = new EmailUser();
        $event = new EmailUserAdded($emailUser);

        $emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);
        $emailAddressVisibilityManager->expects(self::once())
            ->method('processEmailUserVisibility')
            ->with($emailUser)
            ->willReturnCallback(function (EmailUser $emailUser) {
                $emailUser->setIsEmailPrivate(true);
            });

        $listener = new UpdateVisibilityForNewEmailUserListener($emailAddressVisibilityManager);
        $listener->onEmailUserAdded($event);

        self::assertTrue($emailUser->isEmailPrivate());
    }
}
