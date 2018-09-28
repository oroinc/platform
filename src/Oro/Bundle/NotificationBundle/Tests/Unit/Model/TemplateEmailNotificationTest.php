<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Component\Testing\Unit\EntityTrait;

class TemplateEmailNotificationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGetTemplateConditions()
    {
        $recipient = new EmailHolderStub();
        $templateName = 'template_name';
        $entityClassName = \stdClass::class;
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getEntity(EmailTemplate::class, [
            'name' => $templateName,
            'entityName' => $entityClassName,
        ]);

        $notification = new TemplateEmailNotification($emailTemplate, [$recipient]);
        self::assertEquals(
            new EmailTemplateCriteria($templateName, $entityClassName),
            $notification->getTemplateCriteria()
        );
    }

    public function testGetRecipients()
    {
        $recipient = new EmailHolderStub();
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->createMock(EmailTemplate::class);

        $notification = new TemplateEmailNotification($emailTemplate, [$recipient]);
        self::assertEquals([$recipient], $notification->getRecipients());
    }
}
