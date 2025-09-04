<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderAfterEvent;
use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderBeforeEvent;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateRenderingContext;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentProcessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error;
use Twig\Template;
use Twig\TemplateWrapper;

class EmailRendererTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private TemplateRenderer&MockObject $templateRenderer;
    private EmailTemplateAttachmentProcessor&MockObject $emailTemplateAttachmentProcessor;
    private TwigEnvironment&MockObject $twigEnvironment;
    private EmailTemplateRenderingContext $emailTemplateRenderingContext;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private HtmlTagHelper&MockObject $htmlTagHelper;
    private EmailRenderer $renderer;

    #[\Override]
    protected function setUp(): void
    {
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
        $this->emailTemplateAttachmentProcessor = $this->createMock(EmailTemplateAttachmentProcessor::class);
        $this->twigEnvironment = $this->createMock(TwigEnvironment::class);
        $this->emailTemplateRenderingContext = new EmailTemplateRenderingContext();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->renderer = new EmailRenderer(
            $this->templateRenderer,
            $this->emailTemplateAttachmentProcessor,
            $this->twigEnvironment,
            $this->emailTemplateRenderingContext,
            $this->eventDispatcher,
            new PropertyAccessor(),
            $this->htmlTagHelper
        );

        $this->setUpLoggerMock($this->renderer);
    }

    public function testRenderEmailTemplate(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content');
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $renderedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setType($emailTemplate->getType())
            ->setSubject('rendered ' . $emailTemplate->getSubject())
            ->setContent('rendered ' . $emailTemplate->getContent());

        $events = [
            new EmailTemplateRenderBeforeEvent($emailTemplate, $templateParams, $templateContext),
            new EmailTemplateRenderAfterEvent(
                $emailTemplate,
                $renderedEmailTemplate,
                $templateParams,
                $templateContext
            ),
        ];

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (Event $event) use (&$events, $templateContext) {
                $expectedEvent = array_shift($events);
                self::assertEquals(get_class($expectedEvent), $event::class);
                self::assertEquals($templateContext, $this->renderer->getCurrentTemplateContext());

                return $event;
            });

        $this->templateRenderer->expects(self::exactly(2))
            ->method('renderTemplate')
            ->willReturnCallback(function ($template, $params) use ($templateParams) {
                self::assertEquals($templateParams, $params);
                if ($template === 'sample subject') {
                    return 'rendered sample subject';
                }
                if ($template === 'sample content') {
                    return 'rendered sample content';
                }
                return '';
            });

        self::assertEmpty($this->renderer->getCurrentTemplateContext());

        $result = $this->renderer->renderEmailTemplate($emailTemplate, $templateParams, $templateContext);

        self::assertEquals('rendered sample subject', $result->getSubject());
        self::assertEquals('rendered sample content', $result->getContent());
        self::assertEmpty($this->renderer->getCurrentTemplateContext());
    }

    public function testRenderEmailTemplateWithAttachments(): void
    {
        $attachment1 = new EmailTemplateAttachmentModel();
        $attachment1->setId(1);
        $attachment1->setFilePlaceholder('entity.file1');

        $attachment2 = new EmailTemplateAttachmentModel();
        $attachment2->setId(2);
        $attachment2->setFilePlaceholder('entity.file2');

        $processedAttachment1 = new EmailTemplateAttachmentModel();
        $processedAttachment1->setId(1);
        $processedAttachment1->setFilePlaceholder('entity.file1');

        $processedAttachment2 = new EmailTemplateAttachmentModel();
        $processedAttachment2->setId(2);
        $processedAttachment2->setFilePlaceholder('entity.file2');

        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content')
            ->addAttachment($attachment1)
            ->addAttachment($attachment2);

        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->templateRenderer->expects(self::exactly(2))
            ->method('renderTemplate')
            ->willReturnCallback(function ($template, $params) use ($templateParams) {
                self::assertEquals($templateParams, $params);
                if ($template === 'sample subject') {
                    return 'rendered subject';
                }
                if ($template === 'sample content') {
                    return 'rendered content';
                }
                return '';
            });

        $this->emailTemplateAttachmentProcessor->expects(self::exactly(2))
            ->method('processAttachment')
            ->willReturnOnConsecutiveCalls($processedAttachment1, $processedAttachment2);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnArgument(0);

        $renderedEmailTemplate = $this->renderer->renderEmailTemplate(
            $emailTemplate,
            $templateParams,
            $templateContext
        );

        self::assertEquals('rendered subject', $renderedEmailTemplate->getSubject());
        self::assertEquals('rendered content', $renderedEmailTemplate->getContent());

        $renderedAttachments = $renderedEmailTemplate->getAttachments();
        self::assertCount(2, $renderedAttachments);
        self::assertSame($processedAttachment1, $renderedAttachments[0]);
        self::assertSame($processedAttachment2, $renderedAttachments[1]);
    }

    public function testRenderEmailTemplateWithNullAttachment(): void
    {
        $attachment1 = new EmailTemplateAttachmentModel();
        $attachment1->setId(1);
        $attachment1->setFilePlaceholder('entity.file1');

        $attachment2 = new EmailTemplateAttachmentModel();
        $attachment2->setId(2);
        $attachment2->setFilePlaceholder('entity.file2');

        $processedAttachment1 = new EmailTemplateAttachmentModel();
        $processedAttachment1->setId(1);
        $processedAttachment1->setFilePlaceholder('entity.file1');

        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content')
            ->addAttachment($attachment1)
            ->addAttachment($attachment2);

        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->templateRenderer->expects(self::exactly(2))
            ->method('renderTemplate')
            ->willReturnCallback(function ($template, $params) use ($templateParams) {
                self::assertEquals($templateParams, $params);
                if ($template === 'sample subject') {
                    return 'rendered subject';
                }
                if ($template === 'sample content') {
                    return 'rendered content';
                }
                return '';
            });

        $this->emailTemplateAttachmentProcessor->expects(self::exactly(2))
            ->method('processAttachment')
            ->willReturnOnConsecutiveCalls($processedAttachment1, null); // Second attachment fails

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnArgument(0);

        $renderedEmailTemplate = $this->renderer->renderEmailTemplate(
            $emailTemplate,
            $templateParams,
            $templateContext
        );

        self::assertEquals('rendered subject', $renderedEmailTemplate->getSubject());
        self::assertEquals('rendered content', $renderedEmailTemplate->getContent());

        $renderedAttachments = $renderedEmailTemplate->getAttachments();
        self::assertCount(1, $renderedAttachments); // Only successful attachment is added
        self::assertSame($processedAttachment1, $renderedAttachments[0]);
    }

    public function testRenderEmailTemplateWhenTwigError(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content');
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $twigError = new Error('Sample TWIG error');

        $this->templateRenderer->expects(self::exactly(2))
            ->method('renderTemplate')
            ->willReturnCallback(function ($template, $params) use ($templateParams, $twigError) {
                self::assertEquals($templateParams, $params);
                if ($template === 'sample subject') {
                    return 'rendered sample subject';
                }
                if ($template === 'sample content') {
                    throw $twigError;
                }
                return '';
            });

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (Event $event) use ($templateContext) {
                self::assertEquals($templateContext, $this->renderer->getCurrentTemplateContext());
                return $event;
            });

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Rendering of email template "{email_template_name}" failed. {message}',
                [
                    'exception' => $twigError,
                    'message' => $twigError->getMessage(),
                    'email_template_name' => $emailTemplate->getName(),
                    'email_template' => $emailTemplate,
                ]
            );

        $this->expectExceptionObject(new EmailTemplateCompilationException($emailTemplate, $twigError));

        self::assertEmpty($this->renderer->getCurrentTemplateContext());

        $this->renderer->renderEmailTemplate($emailTemplate, $templateParams, $templateContext);
    }

    public function testCompile(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content');
        $templateParams = ['sample_key' => 'sample_value'];

        $subject = 'rendered ' . $emailTemplate->getSubject();
        $content = 'rendered ' . $emailTemplate->getContent();

        $this->templateRenderer->expects(self::exactly(2))
            ->method('renderTemplate')
            ->willReturnCallback(function ($template, $params) use ($templateParams, $subject, $content) {
                self::assertEquals($templateParams, $params);
                if ($template === 'sample subject') {
                    return $subject;
                }
                if ($template === 'sample content') {
                    return $content;
                }
                return '';
            });

        self::assertEquals([$subject, $content], $this->renderer->compileMessage($emailTemplate, $templateParams));
    }

    public function testCompilePreview(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content');

        $sanitizedContent = 'sanitized ' . $emailTemplate->getContent();
        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with($emailTemplate->getContent(), 'default', false)
            ->willReturn($sanitizedContent);

        $template = $this->createMock(Template::class);
        $templateWrapper = new TemplateWrapper($this->twigEnvironment, $template);
        $this->twigEnvironment->expects(self::once())
            ->method('createTemplate')
            ->with('{% verbatim %}' . $sanitizedContent . '{% endverbatim %}')
            ->willReturn($templateWrapper);

        $renderedContent = 'rendered ' . $emailTemplate->getContent();
        $template->expects(self::once())
            ->method('render')
            ->willReturn($renderedContent);

        self::assertEquals($renderedContent, $this->renderer->compilePreview($emailTemplate));
    }

    public function testRenderTemplate(): void
    {
        $templateContent = 'sample content';
        $templateParams = ['sample_key' => 'sample_value'];
        $renderedContent = 'rendered ' . $templateContent;

        $this->templateRenderer->expects(self::once())
            ->method('renderTemplate')
            ->with($templateContent, $templateParams)
            ->willReturn($renderedContent);

        self::assertEquals($renderedContent, $this->renderer->renderTemplate($templateContent, $templateParams));
    }

    public function testValidateTemplate(): void
    {
        $templateContent = 'sample content';
        $templateParams = ['sample_key' => 'sample_value'];

        $this->templateRenderer->expects(self::once())
            ->method('validateTemplate')
            ->with($templateContent);

        $this->renderer->validateTemplate($templateContent, $templateParams);
    }

    public function testSetRenderableFields(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content')
            ->setType('custom_type');
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = [];

        // Set custom renderable fields
        $this->renderer->setRenderableFields(['subject']);

        $this->templateRenderer->expects(self::once())
            ->method('renderTemplate')
            ->with('sample subject', $templateParams)
            ->willReturn('rendered subject');

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnArgument(0);

        $renderedTemplate = $this->renderer->renderEmailTemplate(
            $emailTemplate,
            $templateParams,
            $templateContext
        );

        self::assertEquals('rendered subject', $renderedTemplate->getSubject());
        self::assertEquals('', $renderedTemplate->getContent());
    }

    public function testRenderEmailTemplateWithEmptyFields(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('')
            ->setContent(null);
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = [];

        // No template rendering should happen for empty fields
        $this->templateRenderer->expects(self::never())
            ->method('renderTemplate');

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnArgument(0);

        $renderedTemplate = $this->renderer->renderEmailTemplate(
            $emailTemplate,
            $templateParams,
            $templateContext
        );

        self::assertEquals('', $renderedTemplate->getSubject());
        self::assertEquals('', $renderedTemplate->getContent());
    }
}
