<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailTemplateContextCollectEvent;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\TestCase;

class EmailTemplateContextCollectEventTest extends TestCase
{
    public function testGetters(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipients = [new UserStub(42), new UserStub(43)];
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_template_name');
        $templateParams = ['sample_key' => 'sample_value'];

        $event = new EmailTemplateContextCollectEvent($from, $recipients, $emailTemplateCriteria, $templateParams);

        self::assertSame($from, $event->getFrom());
        self::assertSame($recipients, $event->getRecipients());
        self::assertSame($emailTemplateCriteria, $event->getEmailTemplateCriteria());
        self::assertSame($templateParams, $event->getTemplateParams());
    }
}
