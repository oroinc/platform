<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;

/**
 * Class EmailTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Unit\Entity
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EmailTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $entity = new Email();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testFromEmailAddressGetterAndSetter()
    {
        $emailAddress = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');

        $entity = new Email();
        $entity->setFromEmailAddress($emailAddress);

        $this->assertTrue($emailAddress === $entity->getFromEmailAddress());
    }

    public function testRecipientGetterAndSetter()
    {
        $toRecipient = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $toRecipient->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('to'));

        $ccRecipient = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $ccRecipient->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('cc'));

        $bccRecipient = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $bccRecipient->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('bcc'));

        $entity = new Email();
        $entity->addRecipient($toRecipient);
        $entity->addRecipient($ccRecipient);
        $entity->addRecipient($bccRecipient);

        $recipients = $entity->getRecipients();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $recipients);
        $this->assertCount(3, $recipients);
        $this->assertTrue($toRecipient === $recipients[0]);
        $this->assertTrue($ccRecipient === $recipients[1]);
        $this->assertTrue($bccRecipient === $recipients[2]);

        /** @var GroupNodeDefinition $recipients */
        $recipients = $entity->getRecipients('to');
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $recipients);
        $this->assertCount(1, $recipients);
        $this->assertTrue($toRecipient === $recipients->first());

        $recipients = $entity->getRecipients('cc');
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $recipients);
        $this->assertCount(1, $recipients);
        $this->assertTrue($ccRecipient === $recipients->first());

        $recipients = $entity->getRecipients('bcc');
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $recipients);
        $this->assertCount(1, $recipients);
        $this->assertTrue($bccRecipient === $recipients->first());
    }

    public function testFolderGetterAndSetter()
    {
        $folder = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailFolder');

        $entity = new Email();
        $entity->addFolder($folder);

        $this->assertTrue($folder === $entity->getFolders()->first());
    }

    public function testEmailBodyGetterAndSetter()
    {
        $emailBody = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailBody');

        $entity = new Email();
        $entity->setEmailBody($emailBody);

        $this->assertTrue($emailBody === $entity->getEmailBody());
    }

    public function testBeforeSave()
    {
        $entity = new Email();
        $entity->beforeSave();

        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->assertEquals(Email::NORMAL_IMPORTANCE, $entity->getImportance());
        $this->assertGreaterThanOrEqual($createdAt, $entity->getCreated());
    }

    public function testIsHeadGetterAndSetter()
    {
        $entity = new Email();
        $this->assertTrue($entity->isHead());

        $entity->setHead(false);
        $this->assertFalse($entity->isHead());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Email();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return [
            ['subject', 'testSubject'],
            ['fromName', 'testFromName'],
            ['receivedAt', new \DateTime('now', new \DateTimeZone('UTC'))],
            ['sentAt', new \DateTime('now', new \DateTimeZone('UTC'))],
            ['importance', Email::HIGH_IMPORTANCE],
            ['internalDate', new \DateTime('now', new \DateTimeZone('UTC'))],
            ['messageId', 'testMessageId'],
            ['xMessageId', 'testXMessageId'],
            ['threadId', ['testThreadId']],
            ['xThreadId', 'testxXThreadId'],
            ['refs', 'testRefs'],
            ['seen', true],
            ['seen', ''],
            ['seen', 0],
            ['seen', 1],
        ];
    }
}
