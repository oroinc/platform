<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\EmailTemplateCandidates\EmailTemplateCandidatesProviderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateRenderingContext;
use Oro\Bundle\EmailBundle\Twig\EmailExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private EmailTemplateCandidatesProviderInterface&MockObject $emailTemplateCandidatesProvider;
    private EmailTemplateRenderingContext $emailTemplateRenderingContext;
    private EmailExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->emailTemplateCandidatesProvider = $this->createMock(EmailTemplateCandidatesProviderInterface::class);
        $this->emailTemplateRenderingContext = new EmailTemplateRenderingContext();

        $container = self::getContainerBuilder()
            ->add(ManagerRegistry::class, $managerRegistry)
            ->add(EmailTemplateCandidatesProviderInterface::class, $this->emailTemplateCandidatesProvider)
            ->add(EmailTemplateRenderingContext::class, $this->emailTemplateRenderingContext)
            ->getContainer($this);

        $this->extension = new EmailExtension($container);
    }

    public function testGetEmailTemplateCandidatesWithoutContext(): void
    {
        $templateName = 'sample_name';
        $templateNames = ['@db:/sample_name', 'sample_name'];
        $this->emailTemplateCandidatesProvider->expects(self::once())
            ->method('getCandidatesNames')
            ->with(new EmailTemplateCriteria($templateName), [])
            ->willReturn($templateNames);

        self::assertEquals(
            $templateNames,
            self::callTwigFunction(
                $this->extension,
                'oro_get_email_template',
                [$templateName]
            )
        );
    }

    public function testGetEmailTemplateCandidatesWithExplicitContext(): void
    {
        $templateName = 'sample_name';
        $localizationId = 42;
        $templateNames = ['@db:localization=' . $localizationId . '/sample_name', '@db:/sample_name', 'sample_name'];
        $this->emailTemplateCandidatesProvider->expects(self::once())
            ->method('getCandidatesNames')
            ->with(new EmailTemplateCriteria($templateName), ['localization' => $localizationId])
            ->willReturn($templateNames);

        self::assertEquals(
            $templateNames,
            self::callTwigFunction(
                $this->extension,
                'oro_get_email_template',
                [$templateName, ['localization' => $localizationId]]
            )
        );
    }

    public function testGetEmailTemplateCandidatesWithRenderingContext(): void
    {
        $templateName = 'sample_name';
        $localizationId = 42;
        $templateNames = ['@db:localization=' . $localizationId . '/sample_name', '@db:/sample_name', 'sample_name'];
        $this->emailTemplateCandidatesProvider->expects(self::once())
            ->method('getCandidatesNames')
            ->with(
                new EmailTemplateCriteria($templateName),
                ['localization' => $localizationId, 'sample_key' => 'sample_value']
            )
            ->willReturn($templateNames);

        $this->emailTemplateRenderingContext->set('localization', 44);
        $this->emailTemplateRenderingContext->set('sample_key', 'sample_value');

        self::assertEquals(
            $templateNames,
            self::callTwigFunction(
                $this->extension,
                'oro_get_email_template',
                [$templateName, ['localization' => $localizationId]]
            )
        );
    }
}
