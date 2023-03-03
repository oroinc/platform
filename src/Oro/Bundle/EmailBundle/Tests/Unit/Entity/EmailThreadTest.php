<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

class EmailThreadTest extends \PHPUnit\Framework\TestCase
{
    public function testEmailsGetterAndSetter()
    {
        $email = $this->createMock(Email::class);

        $entity = new EmailThread();
        $entity->addEmail($email);

        $this->assertSame($email, $entity->getEmails()->first());
    }

    public function testBeforeSave()
    {
        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $entity = new Email();
        $entity->beforeSave();

        $this->assertEquals(Email::NORMAL_IMPORTANCE, $entity->getImportance());
        $this->assertGreaterThanOrEqual($createdAt, $entity->getCreated());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $emailThread = new EmailThread();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($emailThread, $property, $value);
        $this->assertEquals($value, $accessor->getValue($emailThread, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['lastUnseenEmail', new Email()],
        ];
    }
}
