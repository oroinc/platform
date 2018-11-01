<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Oro\Component\Testing\Unit\EntityTrait;

class TemplateMassNotificationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGetTemplateConditions()
    {
        /** @var EmailHolderInterface $recipient */
        $recipient = $this->createMock(EmailHolderInterface::class);
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->createMock(EmailTemplateInterface::class);
        $criteria = new EmailTemplateCriteria('template name');

        $notification = new TemplateMassNotification(
            'some',
            'some@some.com',
            [$recipient],
            $emailTemplate,
            $criteria
        );

        self::assertEquals($criteria, $notification->getTemplateCriteria());
    }

    public function testGetRecipients()
    {
        /** @var EmailHolderInterface $recipient */
        $recipient = $this->createMock(EmailHolderInterface::class);
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->createMock(EmailTemplateInterface::class);

        $notification = new TemplateMassNotification(
            'some',
            'some@some.com',
            [$recipient],
            $emailTemplate,
            new EmailTemplateCriteria('template name')
        );

        self::assertEquals([$recipient], $notification->getRecipients());
    }

    public function testGetSubject()
    {
        /** @var EmailHolderInterface $recipient */
        $recipient = $this->createMock(EmailHolderInterface::class);
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->createMock(EmailTemplateInterface::class);
        $subject = 'Subject';

        $notification = new TemplateMassNotification(
            'some',
            'some@some.com',
            [$recipient],
            $emailTemplate,
            new EmailTemplateCriteria('template name'),
            $subject
        );

        self::assertEquals($subject, $notification->getSubject());
    }
}
