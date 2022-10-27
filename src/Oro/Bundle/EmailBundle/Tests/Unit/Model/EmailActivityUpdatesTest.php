<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailActivityUpdates;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\EmailAddress;

class EmailActivityUpdatesTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldFilterOutEntitiesIfOwnerIsMissing()
    {
        $emailAddress = new EmailAddress();
        $emailOwner = new TestEmailOwner(123);
        $emailAddress->setOwner($emailOwner);

        $emailAddressWithoutOwner = new EmailAddress();

        $emailOwnerProvider = $this->createMock(EmailOwnersProvider::class);
        $emailOwnerProvider->expects($this->once())
            ->method('hasEmailsByOwnerEntity')
            ->with($this->identicalTo($emailOwner))
            ->willReturn(true);

        $emailActivityUpdates = new EmailActivityUpdates($emailOwnerProvider);
        $emailActivityUpdates->processUpdatedEmailAddresses([
            $emailAddress,
            $emailAddressWithoutOwner,
        ]);

        $result = $emailActivityUpdates->getFilteredOwnerEntitiesToUpdate();

        $this->assertCount(1, $result);
        $this->assertSame($emailOwner, $result[0]);
    }

    public function testShouldFilterOutEntitiesIfHasEmailsByOwnerEntityIsFalse()
    {
        $emailAddress1 = new EmailAddress();
        $emailOwner1 = new TestEmailOwner(123);
        $emailAddress1->setOwner($emailOwner1);

        $emailAddress2 = new EmailAddress();
        $emailOwner2 = new TestEmailOwner(12345);
        $emailAddress2->setOwner($emailOwner2);

        $emailOwnerProvider = $this->createMock(EmailOwnersProvider::class);
        $emailOwnerProvider->expects($this->exactly(2))
            ->method('hasEmailsByOwnerEntity')
            ->withConsecutive(
                [$this->identicalTo($emailOwner1)],
                [$this->identicalTo($emailOwner2)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $emailActivityUpdates = new EmailActivityUpdates($emailOwnerProvider);
        $emailActivityUpdates->processUpdatedEmailAddresses([
            $emailAddress1,
            $emailAddress2,
        ]);

        $result = $emailActivityUpdates->getFilteredOwnerEntitiesToUpdate();

        $this->assertCount(1, $result);
        $this->assertSame($emailOwner1, $result[0]);
    }
}
