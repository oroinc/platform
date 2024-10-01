<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\EmailTemplateCandidates\EmailTemplateCandidatesProviderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateProvider;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader\EmailTemplateLoaderInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Error\LoaderError;

class EmailTemplateProviderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EmailTemplateLoaderInterface|MockObject $emailTemplateLoader;

    private EmailTemplateCandidatesProviderInterface|MockObject $emailTemplateCandidatesProvider;

    private EmailTemplateProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailTemplateLoader = $this->createMock(EmailTemplateLoaderInterface::class);
        $this->emailTemplateCandidatesProvider = $this->createMock(EmailTemplateCandidatesProviderInterface::class);

        $this->provider = new EmailTemplateProvider(
            $this->emailTemplateLoader,
            $this->emailTemplateCandidatesProvider
        );

        $this->setUpLoggerMock($this->provider);
    }

    public function testLoadEmailTemplateWhenNoCandidates(): void
    {
        $templateName = 'sample_name';
        $templateContext = ['sample_key' => 'sample_value'];

        $emailTemplateCriteria = new EmailTemplateCriteria($templateName);
        $this->emailTemplateCandidatesProvider
            ->expects(self::once())
            ->method('getCandidatesNames')
            ->with($emailTemplateCriteria, $templateContext)
            ->willReturn([]);

        self::assertNull($this->provider->loadEmailTemplate($emailTemplateCriteria, $templateContext));
    }

    public function testLoadEmailTemplateWhenHasCandidates(): void
    {
        $templateName = 'sample_name';
        $name1 = '@db:/' . $templateName;
        $name2 = $templateName;
        $templateContext = ['sample_key' => 'sample_value'];

        $emailTemplateCriteria = new EmailTemplateCriteria($templateName);
        $this->emailTemplateCandidatesProvider
            ->expects(self::once())
            ->method('getCandidatesNames')
            ->with($emailTemplateCriteria, $templateContext)
            ->willReturn([$name1, $name2]);

        $this->emailTemplateLoader
            ->expects(self::exactly(2))
            ->method('exists')
            ->withConsecutive([$name1], [$name2])
            ->willReturnOnConsecutiveCalls(false, true);

        $emailTemplateModel = new EmailTemplateModel();
        $this->emailTemplateLoader
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->with($name2)
            ->willReturn($emailTemplateModel);

        self::assertSame(
            $emailTemplateModel,
            $this->provider->loadEmailTemplate($emailTemplateCriteria, $templateContext)
        );
    }

    public function testLoadEmailTemplateWhenHasCandidatesAndNoExplicitLocalization(): void
    {
        $templateName = 'sample_name';
        $name1 = '@db:/' . $templateName;
        $name2 = $templateName;
        $templateContext = ['sample_key' => 'sample_value'];

        $emailTemplateCriteria = new EmailTemplateCriteria($templateName);
        $this->emailTemplateCandidatesProvider
            ->expects(self::once())
            ->method('getCandidatesNames')
            ->with($emailTemplateCriteria, $templateContext)
            ->willReturn([$name1, $name2]);

        $this->emailTemplateLoader
            ->expects(self::exactly(2))
            ->method('exists')
            ->withConsecutive([$name1], [$name2])
            ->willReturnOnConsecutiveCalls(false, true);

        $emailTemplateModel = new EmailTemplateModel();
        $this->emailTemplateLoader
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->with($name2)
            ->willReturn($emailTemplateModel);

        self::assertSame(
            $emailTemplateModel,
            $this->provider->loadEmailTemplate($emailTemplateCriteria, $templateContext)
        );
    }

    public function testLoadEmailTemplateWhenHasCandidatesAndNotFound(): void
    {
        $templateName = 'sample_name';
        $name1 = '@db:/' . $templateName;
        $name2 = $templateName;
        $templateContext = ['sample_key' => 'sample_value'];

        $emailTemplateCriteria = new EmailTemplateCriteria($templateName);
        $this->emailTemplateCandidatesProvider
            ->expects(self::once())
            ->method('getCandidatesNames')
            ->with($emailTemplateCriteria, $templateContext)
            ->willReturn([$name1, $name2]);

        $this->emailTemplateLoader
            ->expects(self::exactly(2))
            ->method('exists')
            ->withConsecutive([$name1], [$name2])
            ->willReturnOnConsecutiveCalls(false, false);

        $this->emailTemplateLoader
            ->expects(self::never())
            ->method('getEmailTemplate');

        $exception = new LoaderError(
            sprintf('Unable to find one of the following email templates: "%s".', implode('", "', [$name1, $name2]))
        );
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to load email template "{name}": {message}',
                [
                    'name' => $emailTemplateCriteria->getName(),
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );

        $this->provider->loadEmailTemplate($emailTemplateCriteria, $templateContext);
    }
}
