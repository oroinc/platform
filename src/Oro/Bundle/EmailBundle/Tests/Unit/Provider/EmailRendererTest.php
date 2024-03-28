<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderAfterEvent;
use Oro\Bundle\EmailBundle\Event\EmailTemplateRenderBeforeEvent;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateRenderingContext;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
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

    private TemplateRenderer|MockObject $templateRenderer;

    private TwigEnvironment|MockObject $twigEnvironment;

    private EmailTemplateRenderingContext $emailTemplateRenderingContext;

    private EventDispatcherInterface|MockObject $eventDispatcher;

    private HtmlTagHelper|MockObject $htmlTagHelper;

    private EmailRenderer $renderer;

    protected function setUp(): void
    {
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
        $this->twigEnvironment = $this->createMock(TwigEnvironment::class);
        $this->emailTemplateRenderingContext = new EmailTemplateRenderingContext();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->renderer = new EmailRenderer(
            $this->templateRenderer,
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

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (Event $event) use (&$events, $templateContext) {
                self::assertEquals($event, array_shift($events));
                self::assertEquals($templateContext, $this->renderer->getCurrentTemplateContext());

                return $event;
            });

        $this->templateRenderer
            ->expects(self::exactly(2))
            ->method('renderTemplate')
            ->withConsecutive(
                [$emailTemplate->getSubject(), $templateParams],
                [$emailTemplate->getContent(), $templateParams]
            )
            ->willReturnOnConsecutiveCalls(
                $renderedEmailTemplate->getSubject(),
                $renderedEmailTemplate->getContent()
            );

        self::assertEmpty($this->renderer->getCurrentTemplateContext());

        self::assertEquals(
            $renderedEmailTemplate,
            $this->renderer->renderEmailTemplate($emailTemplate, $templateParams, $templateContext)
        );

        self::assertEmpty($this->renderer->getCurrentTemplateContext());
    }

    public function testRenderEmailTemplateWhenTwigError(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content');
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $renderedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setType($emailTemplate->getType())
            ->setSubject('rendered ' . $emailTemplate->getSubject());

        $events = [
            new EmailTemplateRenderBeforeEvent($emailTemplate, $templateParams, $templateContext),
            new EmailTemplateRenderAfterEvent(
                $emailTemplate,
                $renderedEmailTemplate,
                $templateParams,
                $templateContext
            ),
        ];

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (Event $event) use (&$events, $templateContext) {
                self::assertEquals($event, array_shift($events));
                self::assertEquals($templateContext, $this->renderer->getCurrentTemplateContext());

                return $event;
            });

        $twigError = new Error('Sample TWIG error');
        $this->templateRenderer
            ->expects(self::exactly(2))
            ->method('renderTemplate')
            ->withConsecutive(
                [$emailTemplate->getSubject(), $templateParams],
                [$emailTemplate->getContent(), $templateParams]
            )
            ->willReturnOnConsecutiveCalls(
                $renderedEmailTemplate->getSubject(),
                self::throwException($twigError)
            );

        $this->loggerMock
            ->expects(self::once())
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

        self::assertEquals(
            $renderedEmailTemplate,
            $this->renderer->renderEmailTemplate($emailTemplate, $templateParams, $templateContext)
        );

        self::assertEmpty($this->renderer->getCurrentTemplateContext());
    }

    public function testCompile(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content');
        $templateParams = ['sample_key' => 'sample_value'];

        $subject = 'rendered ' . $emailTemplate->getSubject();
        $content = 'rendered ' . $emailTemplate->getContent();
        $this->templateRenderer
            ->expects(self::exactly(2))
            ->method('renderTemplate')
            ->withConsecutive(
                [$emailTemplate->getSubject(), $templateParams],
                [$emailTemplate->getContent(), $templateParams]
            )
            ->willReturnOnConsecutiveCalls(
                $subject,
                $content
            );

        self::assertEquals([$subject, $content], $this->renderer->compileMessage($emailTemplate, $templateParams));
    }

    public function testCompilePreview(): void
    {
        $emailTemplate = (new EmailTemplateModel('sample_name'))
            ->setSubject('sample subject')
            ->setContent('sample content');

        $sanitizedContent = 'sanitized ' . $emailTemplate->getContent();
        $this->htmlTagHelper
            ->expects(self::once())
            ->method('sanitize')
            ->with($emailTemplate->getContent(), 'default', false)
            ->willReturn($sanitizedContent);

        $template = $this->createMock(Template::class);
        $templateWrapper = new TemplateWrapper($this->twigEnvironment, $template);
        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->with('{% verbatim %}' . $sanitizedContent . '{% endverbatim %}')
            ->willReturn($templateWrapper);

        $renderedContent = 'rendered ' . $emailTemplate->getContent();
        $template
            ->expects(self::once())
            ->method('render')
            ->willReturn($renderedContent);

        self::assertEquals($renderedContent, $this->renderer->compilePreview($emailTemplate));
    }

    public function testRenderTemplate(): void
    {
        $templateContent = 'sample content';
        $templateParams = ['sample_key' => 'sample_value'];
        $renderedContent = 'rendered ' . $templateContent;

        $this->templateRenderer
            ->expects(self::once())
            ->method('renderTemplate')
            ->with($templateContent)
            ->willReturn($renderedContent);

        self::assertEquals($renderedContent, $this->renderer->renderTemplate($templateContent, $templateParams));
    }

    public function testValidateTemplate(): void
    {
        $templateContent = 'sample content';
        $templateParams = ['sample_key' => 'sample_value'];

        $this->templateRenderer
            ->expects(self::once())
            ->method('validateTemplate')
            ->with($templateContent);

        $this->renderer->validateTemplate($templateContent, $templateParams);
    }
}
