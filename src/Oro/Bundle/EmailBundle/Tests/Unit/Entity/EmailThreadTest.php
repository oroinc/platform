<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class EmailThreadTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Unit\Entity
 */
class EmailThreadTest extends \PHPUnit\Framework\TestCase
{
    public function testEmailsGetterAndSetter()
    {
        $email = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');

        $entity = new EmailThread();
        $entity->addEmail($email);

        $this->assertTrue($email === $entity->getEmails()->first());
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
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $emailThread = new EmailThread();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($emailThread, $property, $value);
        $this->assertEquals($value, $accessor->getValue($emailThread, $property));
    }

    public function propertiesDataProvider()
    {
        return [
            ['lastUnseenEmail', new Email()],
        ];
    }
}
