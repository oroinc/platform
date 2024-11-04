<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateProvider;
use Oro\Bundle\EmailBundle\Provider\RenderedEmailTemplateProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RenderedEmailTemplateProviderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EmailTemplateProvider|MockObject $emailTemplateProvider;

    private EmailRenderer|MockObject $emailRenderer;

    private RenderedEmailTemplateProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailTemplateProvider = $this->createMock(EmailTemplateProvider::class);
        $this->emailRenderer = $this->createMock(EmailRenderer::class);

        $this->provider = new RenderedEmailTemplateProvider(
            $this->emailTemplateProvider,
            $this->emailRenderer
        );

        $this->setUpLoggerMock($this->provider);
    }

    public function testFindAndRenderEmailTemplateWhenNotFound(): void
    {
        $templateName = 'sample_name';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => 42];

        $this->emailTemplateProvider
            ->expects(self::once())
            ->method('loadEmailTemplate')
            ->with($templateName, $templateContext)
            ->willReturn(null);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Could not find email template for the given criteria',
                ['templateName' => $templateName, 'entityName' => '', 'templateContext' => $templateContext]
            );

        $this->expectExceptionObject(new EmailTemplateNotFoundException($templateName));

        $this->provider->findAndRenderEmailTemplate($templateName, $templateParams, $templateContext);
    }

    public function testFindAndRenderEmailTemplate(): void
    {
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_name', null, ['context_key' => 'context_value']);
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => 42];

        $emailTemplateModel = new EmailTemplateModel();
        $this->emailTemplateProvider
            ->expects(self::once())
            ->method('loadEmailTemplate')
            ->with($emailTemplateCriteria, $templateContext)
            ->willReturn($emailTemplateModel);

        $renderedEmailTemplate = (new EmailTemplateModel())
            ->setSubject('Rendered subject')
            ->setContent('Rendered content');
        $this->emailRenderer
            ->expects(self::once())
            ->method('renderEmailTemplate')
            ->with($emailTemplateModel, $templateParams, $templateContext)
            ->willReturn($renderedEmailTemplate);

        $this->assertLoggerNotCalled();

        self::assertSame(
            $renderedEmailTemplate,
            $this->provider->findAndRenderEmailTemplate($emailTemplateCriteria, $templateParams, $templateContext)
        );
    }
}
