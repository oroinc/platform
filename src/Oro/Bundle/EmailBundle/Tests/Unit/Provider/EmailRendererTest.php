<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEntityForVariableProvider;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorRegistry;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

class EmailRendererTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateRendererConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var EmailRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $variablesProcessorRegistry = $this->createMock(VariableProcessorRegistry::class);

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects(self::any())
            ->method('sanitize')
            ->with(self::isType(\PHPUnit\Framework\Constraint\IsType::TYPE_STRING), 'default', false)
            ->willReturnCallback(static function (string $content) {
                return $content . '(sanitized)';
            });

        $translation = $this->createMock(TranslatorInterface::class);
        $translation->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $environment = new Environment(new ArrayLoader(), ['strict_variables' => true]);
        $environment->addExtension(new SandboxExtension(new SecurityPolicy()));
        $environment->addExtension(new HttpKernelExtension());
        $environment->addExtension(new HtmlTagExtension($this->container));

        $this->renderer = new EmailRenderer(
            $environment,
            $this->configProvider,
            $variablesProcessorRegistry,
            $translation,
            (new InflectorFactory())->build()
        );

        $this->renderer->setHtmlTagHelper($htmlTagHelper);
    }

    private function getEmailTemplate(string $content, string $subject = ''): EmailTemplateInterface
    {
        $emailTemplate = new EmailTemplateModel();
        $emailTemplate->setContent($content);
        $emailTemplate->setSubject($subject);

        return $emailTemplate;
    }

    private function expectVariables(array $entityVariableProcessors = [], array $systemVariableValues = []): void
    {
        $entityVariableProcessorsMap = [];
        foreach ($entityVariableProcessors as $entityClass => $processors) {
            $entityVariableProcessorsMap[] = [$entityClass, $processors];
        }
        $this->configProvider->expects(self::any())
            ->method('getEntityVariableProcessors')
            ->willReturnMap($entityVariableProcessorsMap);
        $this->configProvider->expects(self::any())
            ->method('getSystemVariableValues')
            ->willReturn($systemVariableValues);
    }

    public function testCompilePreview(): void
    {
        $entity = new TestEntityForVariableProvider();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties' => [],
                'methods' => [get_class($entity) => ['getField1']],
                'accessors' => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => [],
            ]);

        $template = 'test <a href="http://example.com">test</a> {{ system.testVar }}';

        $emailTemplate = new EmailTemplateEntity();
        $emailTemplate->setContent($template);
        $emailTemplate->setSubject('');

        self::assertSame($template.'(sanitized)', $this->renderer->compilePreview($emailTemplate));
    }

    public function testCompileMessage(): void
    {
        $entity = new TestEntityForVariableProvider();
        $entity->setField1('Test');
        $entityClass = get_class($entity);
        $systemVars = ['testVar' => 'test_system'];
        $entityVariableProcessors = [$entityClass => []];
        $defaultFormatters = [$entityClass => ['field1' => 'formatter1']];

        $subject = 'subject';
        $content = 'test '
            . '<a href="http://example.com">test</a>'
            . ' {{ entity.getField1()|oro_html_sanitize }}'
            . ' {{ entity.field2|trim|raw }}'
            . ' {{ max(0, 2) }}'
            . ' {{ system.testVar }}'
            . ' N/A';

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties' => [],
                'methods' => [$entityClass => ['getField1']],
                'accessors' => [$entityClass => ['field1' => 'getField1']],
                'default_formatters' => $defaultFormatters,
            ]);
        $this->expectVariables($entityVariableProcessors, $systemVars);

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('oro_ui.html_tag_helper')
            ->willReturn($htmlTagHelper);

        $emailTemplate = $this->getEmailTemplate($content, $subject);
        $templateParams = [
            'entity' => $entity,
            'system' => $systemVars,
        ];

        $result = $this->renderer->compileMessage($emailTemplate, $templateParams);
        $expectedContent = 'test <a href="http://example.com">test</a>   2 test_system N/A';

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertSame($subject, $result[0]);
        self::assertSame($expectedContent, $result[1]);
    }

    public function testRenderTemplate(): void
    {
        $template = 'test '
            . "\n"
            . '{{ entity.field1 }}'
            . "\n"
            . '{{ system.currentDate }}';

        $entity = new TestEntityForVariableProvider();
        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties' => [],
                'methods' => [get_class($entity) => ['getField1']],
                'accessors' => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => [],
            ]);
        $this->expectVariables([
            get_class($entity) => [],
        ], ['currentDate' => '10-12-2019']);

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('oro_ui.html_tag_helper')
            ->willReturn($htmlTagHelper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);

        $expectedRenderedResult =
            'test '
            . "\n"
            . '10-12-2019';
        self::assertSame($expectedRenderedResult, $result);
    }
}
