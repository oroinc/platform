<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderAfterEvent;
use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderBeforeEvent;
use Oro\Bundle\EmailBundle\EventListener\EmailTemplateRenderLocalizationAwareEventListener;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailTemplateRenderLocalizationAwareEventListenerTest extends TestCase
{
    private LocalizationProviderInterface&MockObject $currentLocalizationProvider;
    private EmailTemplateRenderLocalizationAwareEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentLocalizationProvider = $this->createMock(LocalizationProviderInterface::class);

        $this->listener = new EmailTemplateRenderLocalizationAwareEventListener($this->currentLocalizationProvider);
    }

    public function testShouldSkipWhenNoLocalizationInTemplateContext(): void
    {
        $emailTemplate = new EmailTemplateModel('sample_template_name');
        $templateParams = ['entity' => new UserStub(42)];
        $templateContext = ['sample_key' => 'sample_value'];

        $this->currentLocalizationProvider->expects(self::never())
            ->method(self::anything());

        $eventBefore = new EmailTemplateRenderBeforeEvent($emailTemplate, $templateParams, $templateContext);
        $this->listener->onRenderBefore($eventBefore);

        $renderedEmailTemplate = clone $emailTemplate;

        $eventAfter = new EmailTemplateRenderAfterEvent(
            $emailTemplate,
            $renderedEmailTemplate,
            $templateParams,
            $templateContext
        );
        $this->listener->onRenderAfter($eventAfter);
    }

    public function testShouldSwitchLocalizationWhenHasLocalizationInTemplateContext(): void
    {
        $emailTemplate = new EmailTemplateModel('sample_template_name');
        $templateParams = ['entity' => new UserStub(42)];
        $localization = new LocalizationStub(200);
        $templateContext = ['localization' => $localization];
        $currentLocalization = new LocalizationStub(100);

        $this->currentLocalizationProvider->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn($currentLocalization);

        $this->currentLocalizationProvider->expects(self::exactly(2))
            ->method('setCurrentLocalization')
            ->withConsecutive([$localization], [$currentLocalization]);

        $eventBefore = new EmailTemplateRenderBeforeEvent($emailTemplate, $templateParams, $templateContext);
        $this->listener->onRenderBefore($eventBefore);

        $renderedEmailTemplate = clone $emailTemplate;

        $eventAfter = new EmailTemplateRenderAfterEvent(
            $emailTemplate,
            $renderedEmailTemplate,
            $templateParams,
            $templateContext
        );
        $this->listener->onRenderAfter($eventAfter);
    }

    public function testShouldSwitchLocalizationWhenHasLocalizationInTemplateContextAndNoCurrentLocalization(): void
    {
        $emailTemplate = new EmailTemplateModel('sample_template_name');
        $templateParams = ['entity' => new UserStub(42)];
        $localization = new LocalizationStub(200);
        $templateContext = ['localization' => $localization];

        $this->currentLocalizationProvider->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->currentLocalizationProvider->expects(self::exactly(2))
            ->method('setCurrentLocalization')
            ->withConsecutive([$localization], [null]);

        $eventBefore = new EmailTemplateRenderBeforeEvent($emailTemplate, $templateParams, $templateContext);
        $this->listener->onRenderBefore($eventBefore);

        $renderedEmailTemplate = clone $emailTemplate;

        $eventAfter = new EmailTemplateRenderAfterEvent(
            $emailTemplate,
            $renderedEmailTemplate,
            $templateParams,
            $templateContext
        );
        $this->listener->onRenderAfter($eventAfter);
    }
}
