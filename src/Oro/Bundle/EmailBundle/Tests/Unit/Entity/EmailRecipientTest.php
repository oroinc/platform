<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Component\Testing\ReflectionUtil;

class EmailRecipientTest extends \PHPUnit\Framework\TestCase
{
    public function testIdGetter()
    {
        $entity = new EmailRecipient();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testNameGetterAndSetter()
    {
        $entity = new EmailRecipient();
        $entity->setName('test');
        $this->assertEquals('test', $entity->getName());
    }

    public function testTypeGetterAndSetter()
    {
        $entity = new EmailRecipient();
        $entity->setType('test');
        $this->assertEquals('test', $entity->getType());
    }

    public function testEmailAddressGetterAndSetter()
    {
        $emailAddress = $this->createMock(EmailAddress::class);

        $entity = new EmailRecipient();
        $entity->setEmailAddress($emailAddress);

        $this->assertSame($emailAddress, $entity->getEmailAddress());
    }

    public function testEmailGetterAndSetter()
    {
        $email = $this->createMock(Email::class);

        $entity = new EmailRecipient();
        $entity->setEmail($email);

        $this->assertSame($email, $entity->getEmail());
    }
}
