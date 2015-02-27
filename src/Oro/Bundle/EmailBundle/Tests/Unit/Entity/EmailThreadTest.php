<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;

/**
 * Class EmailThreadTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Unit\Entity
 */
class EmailThreadTest extends \PHPUnit_Framework_TestCase
{
    public function testEmailsGetterAndSetter()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');

        $entity = new EmailThread();
        $entity->addEmail($email);

        $this->assertTrue($email === $entity->getEmails()->first());
    }

    public function testBeforeSave()
    {
        $entity = new Email();
        $entity->beforeSave();

        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));

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
