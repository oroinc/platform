<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailEntityNameProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

class EmailEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailEntityNameProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new EmailEntityNameProvider(PropertyAccess::createPropertyAccessor());
    }

    public function testGetNameDQL()
    {
        $this->assertFalse($this->provider->getNameDQL('format', 'loc', 'class', 'alias'));
    }

    public function testGetNameNotSupportedFormat()
    {
        $this->assertFalse($this->provider->getNameDQL('format', 'loc', 'class', 'alias'));
    }

    public function testEmailOwnerInterfaceEmailOnly()
    {
        $owner = new TestEmailOwner();
        $owner->setPrimaryEmail('jdoe@example.com');

        $this->assertEquals(
            $owner->getPrimaryEmail(),
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceSupportWithoutFields()
    {
        $owner = new TestEmailOwner();
        $owner->setEmailFields([]);

        $this->assertFalse($this->provider->getName('email', 'en', $owner));
    }

    public function testEmailOwnerInterfaceSupportWithNotExistingFields()
    {
        $owner = new TestEmailOwner();
        $owner->setEmailFields(['invalid']);

        $this->assertFalse($this->provider->getName('email', 'en', $owner));
    }

    public function testMultipleEmails()
    {
        $owner = (new TestEmailOwner())
            ->setPrimaryEmail('jdoe1@example.com')
            ->setHomeEmail('jdoe2@example.com');

        $this->assertEquals(
            'jdoe1@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWithFullname()
    {
        $owner = (new TestEmailOwner(42))
            ->setPrimaryEmail('jdoe2@example.com');

        $this->assertEquals(
            'firstName42 lastName42 - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWitFirstnameOnly()
    {
        $owner = (new TestEmailOwner(42))
            ->setPrimaryEmail('jdoe2@example.com')
            ->setLastName('');

        $this->assertEquals(
            'firstName42 - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWithLastnameOnly()
    {
        $owner = (new TestEmailOwner(42))
            ->setPrimaryEmail('jdoe2@example.com')
            ->setFirstName(null);

        $this->assertEquals(
            'lastName42 - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testUnsupportedFormat()
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->never())
            ->method('getEmailFields');

        $this->assertFalse($this->provider->getName('email1', 'en', $owner));
    }
}
