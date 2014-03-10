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
    const LOCALE = 'locale';
    const EMAIL = 'test@example.com';

    /**
     * @var EmailNotification
     */
    protected $notification;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    protected function setUp()
    {
        $this->em = $this
            ->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->provider = $this
            ->getMockBuilder('\Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->notification = new EmailNotification(
            $this->em,
            $this->provider
        );
    }

    public function testSetReminder()
    {
        $reminder = new Reminder();

        $this->notification->setReminder($reminder);
    }

    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Reminder was not set
     */
    public function testGetTemplateWithoutReminder()
    {
        $this->notification->getTemplate();
    }

    public function testGetTemplateByClassName()
    {
        $this->prepareSource();

        $reminder = new Reminder();
        $notification = new EmailNotification(
            $this->em,
            $this->provider
        );
        $notification->setReminder($reminder);
        $notification->getTemplate();
    }

    public function testGetTemplateByConfig()
    {
        $this->prepareSource(true);

        $reminder = new Reminder();
        $notification = new EmailNotification(
            $this->em,
            $this->provider
        );
        $notification->setReminder($reminder);
        $template = $notification->getTemplate(self::LOCALE);
        $this->assertEquals(self::LOCALE, $template->getLocale());
    }

    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Reminder was not set
     */
    public function testGetRecipientEmailsWithoutReminder()
    {
        $this->notification->getRecipientEmails();
    }

    public function testGetRecipientEmails()
    {
        $reminder = new Reminder();
        $user = new User();
        $user->setEmail(self::EMAIL);
        $reminder->setRecipient($user);
        $this->notification->setReminder($reminder);
        $email = $this->notification->getRecipientEmails();

        $this->assertEquals([self::EMAIL], $email);
    }

    protected function prepareSource($hasConfig = false)
    {
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository
            ->expects($this->atLeastOnce())
            ->method('find')
            ->will($this->returnValue(new CalendarEvent()));

        $template = new EmailTemplate();
        $translation = new EmailTemplateTranslation();
        $translation
            ->setLocale(self::LOCALE)
            ->setField('type');
        $translations = new ArrayCollection([$translation]);
        $template->setTranslations($translations);
        $repository
            ->expects($this->atLeastOnce())
            ->method('findOneBy')
            ->will($this->returnValue($template));

        $this->em
            ->expects($this->atLeastOnce())
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

        $this->provider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));
    }
}
