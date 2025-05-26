<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailEntityNameProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use PHPUnit\Framework\TestCase;

class EmailEntityNameProviderTest extends TestCase
{
    private EmailEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new EmailEntityNameProvider(PropertyAccess::createPropertyAccessor());
    }

    public function testGetNameDQL(): void
    {
        $this->assertFalse($this->provider->getNameDQL('format', 'loc', 'class', 'alias'));
    }

    public function testGetNameNotSupportedFormat(): void
    {
        $this->assertFalse($this->provider->getNameDQL('format', 'loc', 'class', 'alias'));
    }

    public function testEmailOwnerInterfaceEmailOnly(): void
    {
        $owner = new TestEmailOwner();
        $owner->setPrimaryEmail('jdoe@example.com');

        $this->assertEquals(
            $owner->getPrimaryEmail(),
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceSupportWithoutFields(): void
    {
        $owner = new TestEmailOwner();
        $owner->setEmailFields([]);

        $this->assertFalse($this->provider->getName('email', 'en', $owner));
    }

    public function testEmailOwnerInterfaceSupportWithNotExistingFields(): void
    {
        $owner = new TestEmailOwner();
        $owner->setEmailFields(['invalid']);

        $this->assertFalse($this->provider->getName('email', 'en', $owner));
    }

    public function testMultipleEmails(): void
    {
        $owner = (new TestEmailOwner())
            ->setPrimaryEmail('jdoe1@example.com')
            ->setHomeEmail('jdoe2@example.com');

        $this->assertEquals(
            'jdoe1@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWithFullname(): void
    {
        $owner = (new TestEmailOwner(42))
            ->setPrimaryEmail('jdoe2@example.com');

        $this->assertEquals(
            'firstName42 lastName42 - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWitFirstnameOnly(): void
    {
        $owner = (new TestEmailOwner(42))
            ->setPrimaryEmail('jdoe2@example.com')
            ->setLastName('');

        $this->assertEquals(
            'firstName42 - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWithLastnameOnly(): void
    {
        $owner = (new TestEmailOwner(42))
            ->setPrimaryEmail('jdoe2@example.com')
            ->setFirstName(null);

        $this->assertEquals(
            'lastName42 - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testUnsupportedFormat(): void
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->never())
            ->method('getEmailFields');

        $this->assertFalse($this->provider->getName('email1', 'en', $owner));
    }
}
