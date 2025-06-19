<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    public function testIdGetter(): void
    {
        $entity = new EmailAddress(1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testEmailGetterAndSetter(): void
    {
        $entity = new EmailAddress();
        $entity->setEmail('test');
        $this->assertEquals('test', $entity->getEmail());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $entity = new EmailAddress(null, $date);
        $this->assertEquals($date, $entity->getCreated());
    }

    public function testUpdatedAtGetterAndSetter(): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $entity = new EmailAddress(null, $date);
        $this->assertEquals($date, $entity->getUpdated());
    }
}
