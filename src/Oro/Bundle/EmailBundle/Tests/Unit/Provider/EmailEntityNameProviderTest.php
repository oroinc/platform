<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailEntityNameProvider;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EmailEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailEntityNameProvider */
    private $provider;

    protected function setUp()
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
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->once())->method('getEmailFields')->willReturn(['testField']);
        $owner->testField = 'jdoe@example.com';

        $this->assertEquals(
            'jdoe@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceSupportWithoutFields()
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->once())->method('getEmailFields')->willReturn([]);

        $this->assertFalse($this->provider->getName('email', 'en', $owner));
    }

    public function testEmailOwnerInterfaceSupportWithNotExistingFields()
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->once())->method('getEmailFields')->willReturn(['testField']);

        $this->assertFalse($this->provider->getName('email', 'en', $owner));
    }

    public function testMultipleEmails()
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->once())->method('getEmailFields')->willReturn(['testField', 'testField2']);
        $owner->testField = 'jdoe1@example.com';
        $owner->testField2 = 'jdoe2@example.com';

        $this->assertEquals(
            'jdoe1@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWithFullname()
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->once())->method('getEmailFields')->willReturn(['testField']);
        $owner->expects($this->once())->method('getFirstname')->willReturn('John');
        $owner->expects($this->once())->method('getLastname')->willReturn('Doe');
        $owner->testField = 'jdoe2@example.com';

        $this->assertEquals(
            'John Doe - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWitFirstnameOnly()
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->once())->method('getEmailFields')->willReturn(['testField']);
        $owner->expects($this->once())->method('getFirstname')->willReturn('John');
        $owner->expects($this->once())->method('getLastname')->willReturn(null);
        $owner->testField = 'jdoe2@example.com';

        $this->assertEquals(
            'John - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testEmailOwnerInterfaceWithLastnameOnly()
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->once())->method('getEmailFields')->willReturn(['testField']);
        $owner->expects($this->once())->method('getFirstname')->willReturn(null);
        $owner->expects($this->once())->method('getLastname')->willReturn('John');
        $owner->testField = 'jdoe2@example.com';

        $this->assertEquals(
            'John - jdoe2@example.com',
            $this->provider->getName('email', 'en', $owner)
        );
    }

    public function testUnsupportedFormat()
    {
        $owner = $this->createMock(EmailOwnerInterface::class);
        $owner->expects($this->never())->method('getEmailFields');

        $this->assertFalse($this->provider->getName('email1', 'en', $owner));
    }
}
