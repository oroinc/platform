<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderBeforeEvent;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\TestCase;

class EmailTemplateRenderBeforeEventTest extends TestCase
{
    public function testGetters(): void
    {
        $emailTemplate = new EmailTemplateModel('sample_template_name');
        $templateParams = ['entity' => new UserStub(42)];
        $localization = new Localization();
        $templateContext = ['localization' => $localization];

        $event = new EmailTemplateRenderBeforeEvent($emailTemplate, $templateParams, $templateContext);

        self::assertSame($emailTemplate, $event->getEmailTemplate());
        self::assertSame($templateParams, $event->getTemplateParams());
        self::assertSame($templateContext, $event->getTemplateContext());
        self::assertSame($localization, $event->getTemplateContextParameter('localization'));
    }
}
