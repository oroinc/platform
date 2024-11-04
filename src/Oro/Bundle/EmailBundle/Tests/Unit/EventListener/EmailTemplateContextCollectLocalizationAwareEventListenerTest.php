<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailTemplateContextCollectEvent;
use Oro\Bundle\EmailBundle\EventListener\EmailTemplateContextCollectLocalizationAwareEventListener;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailTemplateContextCollectLocalizationAwareEventListenerTest extends TestCase
{
    private PreferredLocalizationProviderInterface|MockObject $preferredLocalizationProvider;

    private EmailTemplateContextCollectLocalizationAwareEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->preferredLocalizationProvider = $this->createMock(PreferredLocalizationProviderInterface::class);

        $this->listener = new EmailTemplateContextCollectLocalizationAwareEventListener(
            $this->preferredLocalizationProvider
        );
    }

    public function testShouldSkipWhenLocalizationAlreadySet(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipients = [new UserStub(42)];
        $localization = new Localization();
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_template_name');
        $templateParams = ['sample_key' => 'sample_value'];

        $event = new EmailTemplateContextCollectEvent($from, $recipients, $emailTemplateCriteria, $templateParams);
        $event->setTemplateContextParameter('localization', $localization);

        $this->preferredLocalizationProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onContextCollect($event);

        self::assertSame($localization, $event->getTemplateContextParameter('localization'));
    }

    public function testShouldSkipWhenNoRecipients(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipients = [];
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_template_name');
        $templateParams = ['sample_key' => 'sample_value'];

        $event = new EmailTemplateContextCollectEvent($from, $recipients, $emailTemplateCriteria, $templateParams);

        $this->preferredLocalizationProvider
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($event->getTemplateContextParameter('localization'));

        $this->listener->onContextCollect($event);

        self::assertNull($event->getTemplateContextParameter('localization'));
    }

    public function testShouldSkipWhenNoPreferredLocalization(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipients = [new UserStub(42), new UserStub(43)];
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_template_name');
        $templateParams = ['sample_key' => 'sample_value'];

        $event = new EmailTemplateContextCollectEvent($from, $recipients, $emailTemplateCriteria, $templateParams);

        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with(reset($recipients))
            ->willReturn(null);

        self::assertNull($event->getTemplateContextParameter('localization'));

        $this->listener->onContextCollect($event);

        self::assertNull($event->getTemplateContextParameter('localization'));
    }

    public function testShouldSetLocalizationWhenHasPreferredLocalization(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipients = [new UserStub(42), new UserStub(43)];
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_template_name');
        $templateParams = ['sample_key' => 'sample_value'];

        $event = new EmailTemplateContextCollectEvent($from, $recipients, $emailTemplateCriteria, $templateParams);

        $localization = new Localization();
        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with(reset($recipients))
            ->willReturn($localization);

        self::assertNull($event->getTemplateContextParameter('localization'));

        $this->listener->onContextCollect($event);

        self::assertSame($localization, $event->getTemplateContextParameter('localization'));
    }
}
