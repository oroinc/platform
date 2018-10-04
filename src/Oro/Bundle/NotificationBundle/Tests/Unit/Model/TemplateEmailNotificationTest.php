<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Component\Testing\Unit\EntityTrait;

class TemplateEmailNotificationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetTemplateConditions(): void
    {
        $recipient = new EmailHolderStub();
        $templateName = 'template_name';
        $entityClassName = \stdClass::class;
        $emailTemplateCriteria = new EmailTemplateCriteria($templateName, $entityClassName);

        $notification = new TemplateEmailNotification($emailTemplateCriteria, [$recipient]);
        self::assertEquals(
            new EmailTemplateCriteria($templateName, $entityClassName),
            $notification->getTemplateCriteria()
        );
    }

    public function testGetRecipients(): void
    {
        $recipient = new EmailHolderStub();
        $emailTemplateCriteria = new EmailTemplateCriteria('template');

        $notification = new TemplateEmailNotification($emailTemplateCriteria, [$recipient]);
        self::assertEquals([$recipient], $notification->getRecipients());
    }

    public function testGetEntity(): void
    {
        $entity = new \stdClass();
        $emailTemplateCriteria = new EmailTemplateCriteria('template');

        $notification = new TemplateEmailNotification($emailTemplateCriteria, [], $entity);
        self::assertEquals($entity, $notification->getEntity());

        $notification = new TemplateEmailNotification($emailTemplateCriteria, []);
        self::assertNull($notification->getEntity());
    }
}
