<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Email;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Email\EmailNotification;

class EmailNotificationTest extends \PHPUnit_Framework_TestCase
{
    const LOCALE     = 'locale';
    const CLASS_NAME = 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var EmailNotification
     */
    protected $notification;

    protected function setUp()
    {
        $this->em = $this
            ->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->notification = new EmailNotification($this->em);
    }

    public function testCalendarEvent()
    {
        $calendarEvent = new CalendarEvent();
        $this->notification->setCalendarEvent($calendarEvent);
        $this->assertEquals($calendarEvent, $this->notification->getEntity());
    }

    public function testEmails()
    {
        $emails = ['test@test.com'];
        $this->notification->setEmails($emails);
        $this->assertEquals($emails, $this->notification->getRecipientEmails());
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

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository
            ->expects($this->any())
            ->method('find')
            ->will($this->returnValue(new CalendarEvent()));

        $repository
            ->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($templates));

        $this->em
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->notification->setTemplateName('template_test');
        $this->notification->getTemplate();
    }

    /**
     * @return array
     */
    public function templateProvider()
    {
        return [
            'one'      => [
                'exceptionMessage' => 'Template with name "template_test" for "' . self::CLASS_NAME . '" not found',
                'templates'        => []
            ],
            'multiple' => [
                'exceptionMessage' => 'Multiple templates with name "template_test" for "' . self::CLASS_NAME .
                    '" found',
                'templates'        => [$this->createTemplate(), $this->createTemplate()]
            ]
        ];
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
