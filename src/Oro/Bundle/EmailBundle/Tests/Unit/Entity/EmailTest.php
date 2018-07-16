<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class EmailTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Unit\Entity
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EmailTest extends \PHPUnit\Framework\TestCase
{
    public function testIdGetter()
    {
        $entity = new Email();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testFromEmailAddressGetterAndSetter()
    {
        $emailAddress = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');

        $entity = new Email();
        $entity->setFromEmailAddress($emailAddress);

        $this->assertTrue($emailAddress === $entity->getFromEmailAddress());
    }

    public function testRecipientGetterAndSetter()
    {
        $toRecipient = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $toRecipient->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('to'));

        $ccRecipient = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $ccRecipient->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('cc'));

        $bccRecipient = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
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

    public function testEmailBodyGetterAndSetter()
    {
        $emailBody = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailBody');

        $entity = new Email();
        $entity->setEmailBody($emailBody);

        $this->assertTrue($emailBody === $entity->getEmailBody());
    }

    public function testBeforeSave()
    {
        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $entity = new Email();
        $entity->beforeSave();

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
     * @dataProvider refsDataProvider
     * @param string $set
     * @param array $get
     */
    public function testRefsGetterAndSetter($set, $get)
    {
        $entity = new Email();
        $entity->setRefs($set);
        $this->assertEquals($get, $entity->getRefs());
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

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            ['subject', 'testSubject'],
            ['fromName', 'testFromName'],
            ['sentAt', new \DateTime('now', new \DateTimeZone('UTC'))],
            ['importance', Email::HIGH_IMPORTANCE],
            ['internalDate', new \DateTime('now', new \DateTimeZone('UTC'))],
            ['messageId', 'testMessageId'],
            ['xMessageId', 'testXMessageId'],
            ['thread', new EmailThread()],
            ['xThreadId', 'testxXThreadId'],
            ['multiMessageId', ['MessageId1','MessageId2']],
        ];
    }

    public function refsDataProvider()
    {
        return [
            [null, []],
            ['', []],
            ['ref', []],
            ['<ref>', ['<ref>']],
            ['<ref1><ref2>', ['<ref1>', '<ref2>']],
            ['<ref1> <ref2>', ['<ref1>', '<ref2>']],
            ['<ref1> ref2', ['<ref1>']],
        ];
    }

    public function testSetSubjectOnLongString()
    {
        $activityList = new Email();
        $activityList->setSubject(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc ut sem cursus ligula consectetur iaculis. '
            . 'Sed ac viverra mi, in auctor tortor. Aliquam id est laoreet, ultricies lectus a, aliquam lectus. Aenean'
            . ' ac tristique eros. Integer vestibulum volutpat lacus, eu lobortis sapien condimentum in. Pellentesque '
            . 'a venenatis risus, id placerat nisi. Donec egestas maximus convallis. Cras eleifend leo quis neque '
            . 'rutrum suscipit. Nulla facilisi. Integer vel enim at tellus ornare condimentum. Nunc rhoncus urna nec '
            . 'scelerisque elementum. Pellentesque id ante sapien. Phasellus luctus facilisis massa, eu condimentum '
            . 'justo ultrices at. Curabitur purus diam, aliquet sit amet ante a, aliquet faucibus metus. Nam efficitur'
            . ' tincidunt urna tincidunt tincidunt. Maecenas et dictum enim. Maecenas pellentesque purus et sapien '
            . 'vulputate efficitur. Curabitur egestas gravida venenatis. Nullam efficitur nulla eu augue vestibulum, '
            . 'ut imperdiet nibh pellentesque. Cras ultrices luctus magna vel sodales. Curabituä eget nullam.'
        );

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc ut sem cursus ligula consectetur iaculis. '
            . 'Sed ac viverra mi, in auctor tortor. Aliquam id est laoreet, ultricies lectus a, aliquam lectus. Aenean'
            . ' ac tristique eros. Integer vestibulum volutpat lacus, eu lobortis sapien condimentum in. Pellentesque '
            . 'a venenatis risus, id placerat nisi. Donec egestas maximus convallis. Cras eleifend leo quis neque '
            . 'rutrum suscipit. Nulla facilisi. Integer vel enim at tellus ornare condimentum. Nunc rhoncus urna nec '
            . 'scelerisque elementum. Pellentesque id ante sapien. Phasellus luctus facilisis massa, eu condimentum '
            . 'justo ultrices at. Curabitur purus diam, aliquet sit amet ante a, aliquet faucibus metus. Nam efficitur'
            . ' tincidunt urna tincidunt tincidunt. Maecenas et dictum enim. Maecenas pellentesque purus et sapien '
            . 'vulputate efficitur. Curabitur egestas gravida venenatis. Nullam efficitur nulla eu augue vestibulum, '
            . 'ut imperdiet nibh pellentesque. Cras ultrices luctus magna vel sodales. Curabituä',
            $activityList->getSubject()
        );
    }
}
