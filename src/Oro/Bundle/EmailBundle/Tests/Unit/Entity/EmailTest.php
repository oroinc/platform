<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
        $emailAddress = $this->createMock(EmailAddress::class);

        $entity = new Email();
        $entity->setFromEmailAddress($emailAddress);

        $this->assertSame($emailAddress, $entity->getFromEmailAddress());
    }

    public function testRecipientGetterAndSetter()
    {
        $toRecipient = $this->createMock(EmailRecipient::class);
        $toRecipient->expects($this->any())
            ->method('getType')
            ->willReturn('to');

        $ccRecipient = $this->createMock(EmailRecipient::class);
        $ccRecipient->expects($this->any())
            ->method('getType')
            ->willReturn('cc');

        $bccRecipient = $this->createMock(EmailRecipient::class);
        $bccRecipient->expects($this->any())
            ->method('getType')
            ->willReturn('bcc');

        $entity = new Email();
        $entity->addRecipient($toRecipient);
        $entity->addRecipient($ccRecipient);
        $entity->addRecipient($bccRecipient);

        $recipients = $entity->getRecipients();

        $this->assertInstanceOf(ArrayCollection::class, $recipients);
        $this->assertCount(3, $recipients);
        $this->assertSame($toRecipient, $recipients[0]);
        $this->assertSame($ccRecipient, $recipients[1]);
        $this->assertSame($bccRecipient, $recipients[2]);

        /** @var GroupNodeDefinition $recipients */
        $recipients = $entity->getRecipients('to');
        $this->assertInstanceOf(ArrayCollection::class, $recipients);
        $this->assertCount(1, $recipients);
        $this->assertSame($toRecipient, $recipients->first());

        $recipients = $entity->getRecipients('cc');
        $this->assertInstanceOf(ArrayCollection::class, $recipients);
        $this->assertCount(1, $recipients);
        $this->assertSame($ccRecipient, $recipients->first());

        $recipients = $entity->getRecipients('bcc');
        $this->assertInstanceOf(ArrayCollection::class, $recipients);
        $this->assertCount(1, $recipients);
        $this->assertSame($bccRecipient, $recipients->first());
    }

    public function testEmailBodyGetterAndSetter()
    {
        $emailBody = $this->createMock(EmailBody::class);

        $entity = new Email();
        $entity->setEmailBody($emailBody);

        $this->assertSame($emailBody, $entity->getEmailBody());
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
     */
    public function testRefsGetterAndSetter(?string $set, array $get)
    {
        $entity = new Email();
        $entity->setRefs($set);
        $this->assertEquals($get, $entity->getRefs());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new Email();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
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

    public function refsDataProvider(): array
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
