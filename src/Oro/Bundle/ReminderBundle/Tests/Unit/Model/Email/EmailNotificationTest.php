<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\Email;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\Email\EmailNotification;
use Oro\Bundle\UserBundle\Entity\User;

class EmailNotificationTest extends \PHPUnit_Framework_TestCase
{
    const LOCALE   = 'locale';
    const EMAIL    = 'test@example.com';
    const ENTITY   = 'Namespace\Entity';
    const TEMPLATE = 'template_reminder';
    const SENDER_EMAIL = 'test@mail.com';
    const SENDER_NAME = 'John Doe';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityNameResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sender;

    protected function setUp()
    {
        $this->em = $this
            ->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->provider = $this
            ->getMockBuilder('\Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityNameResolver = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sender = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $this->sender->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue(self::SENDER_EMAIL));
    }

    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Reminder was not set
     */
    public function testGetTemplateWithoutReminder()
    {
        $this->createNotification(false)->getTemplate();
    }

    /**
     * @param string $exceptionMessage
     * @param array  $templates
     * @dataProvider templateProvider
     */
    public function testGetTemplateFromRepository($exceptionMessage, $templates)
    {
        $this->setExpectedException(
            'Oro\\Bundle\\ReminderBundle\\Exception\\InvalidArgumentException',
            $exceptionMessage
        );

        $notification = $this->createNotification(true, false, false, false, $templates);
        $notification->getTemplate();
    }

    /**
     * @return array
     */
    public function templateProvider()
    {
        return [
            'one'      => [
                'exceptionMessage' => 'Template with name "template_reminder" for "Namespace\Entity" not found',
                'templates'        => []
            ],
            'multiple' => [
                'exceptionMessage' => 'Multiple templates with name "template_reminder" for "Namespace\Entity" found',
                'templates'        => [$this->createTemplate(), $this->createTemplate()]
            ]
        ];
    }

    public function testGetTemplateByClassName()
    {
        $notification = $this->createNotification();
        $notification->getTemplate();
    }

    public function testGetTemplateByConfig()
    {
        $notification = $this->createNotification(true);
        $template     = $notification->getTemplate(self::LOCALE);
        $this->assertEquals($this->createTemplate(), $template);
    }

    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Reminder was not set
     */
    public function testGetEntityWithoutReminder()
    {
        $this->createNotification(false)->getEntity();
    }

    public function testGetEntity()
    {
        $notification = $this->createNotification();
        $notification->getEntity();
    }

    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Reminder was not set
     */
    public function testGetRecipientEmailsWithoutReminder()
    {
        $this->createNotification(false)->getRecipientEmails();
    }

    public function testGetRecipientEmails()
    {
        $email = $this->createNotification(true, false, true)->getRecipientEmails();

        $this->assertEquals([self::EMAIL], $email);
    }

    public function testGetSenderEmail()
    {
        $email = $this->createNotification(true, false, true, true)->getSenderEmail();

        $this->assertEquals(self::SENDER_EMAIL, $email);
    }

    public function testGetSenderName()
    {
        $this->entityNameResolver
            ->expects($this->once())
            ->method('getName')
            ->with($this->sender)
            ->will($this->returnValue(self::SENDER_NAME));
        $name = $this->createNotification(true, false, true, true)->getSenderName();

        $this->assertEquals(self::SENDER_NAME, $name);
    }

    /**
     * @param bool  $hasReminder
     * @param bool  $hasConfig
     * @param bool  $hasRecipient
     * @param bool  $hasSender
     * @param array $templates
     * @return EmailNotification
     */
    protected function createNotification(
        $hasReminder = true,
        $hasConfig = false,
        $hasRecipient = false,
        $hasSender = false,
        $templates = null
    ) {
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('find')
            ->will($this->returnValue(new CalendarEvent()));

        $templates = is_array($templates) ? $templates : [$this->createTemplate()];
        $repository
            ->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($templates));

        $this->em
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->provider = $this
            ->getMockBuilder('\Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $config = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $config
            ->expects($this->any())
            ->method('has')
            ->will($this->returnValue($hasConfig));

        $config
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue(self::TEMPLATE));

        $this->provider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $notification = new EmailNotification(
            $this->em,
            $this->provider,
            $this->entityNameResolver
        );

        if ($hasReminder) {
            $reminder = new Reminder();
            if (!$hasConfig) {
                $reminder
                    ->setRelatedEntityClassName(self::ENTITY)
                    ->setRelatedEntityId(1);
            }

            if ($hasRecipient) {
                $user = new User();
                $user->setEmail(self::EMAIL);
                $reminder->setRecipient($user);
            }

            if ($hasSender) {
                $reminder->setSender($this->sender);
            }

            $notification->setReminder($reminder);
        }

        return $notification;
    }

    /**
     * @return EmailTemplate
     */
    protected function createTemplate()
    {
        $template    = new EmailTemplate();
        $translation = new EmailTemplateTranslation();
        $translation
            ->setLocale(self::LOCALE)
            ->setField('type');
        $translations = new ArrayCollection([$translation]);
        $template->setTranslations($translations);

        return $template;
    }
}
