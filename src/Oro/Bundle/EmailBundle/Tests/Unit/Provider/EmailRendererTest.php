<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEntityForVariableProvider;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class EmailRendererTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_VARIABLE_TEMPLATE =
        '{% if %val% is defined %}'
        . '{{ _entity_var("%name%", %val%, %parent%) }}'
        . '{% else %}'
        . '{{ "oro.email.variable.not.found" }}'
        . '{% endif %}';

    /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var \Twig_Sandbox_SecurityPolicy|\PHPUnit\Framework\MockObject\MockObject */
    private $securityPolicy;

    /** @var TemplateRendererConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var VariableProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $variablesProcessorRegistry;

    /** @var TranslatorInterface */
    private $translation;

    /** @var EmailRenderer */
    private $renderer;

    protected function setUp()
    {
        $this->environment = $this->getMockBuilder(\Twig_Environment::class)
            ->setMethods(['render'])
            ->setConstructorArgs([new \Twig_Loader_String(), ['strict_variables' => true]])
            ->getMock();
        $this->securityPolicy = $this->createMock(\Twig_Sandbox_SecurityPolicy::class);
        $this->configProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $this->variablesProcessorRegistry = $this->createMock(VariableProcessorRegistry::class);
        $this->translation = $this->createMock(TranslatorInterface::class);

        $this->translation->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->environment->addExtension(new \Twig_Extension_Sandbox($this->securityPolicy));

        $this->renderer = new EmailRenderer(
            $this->environment,
            $this->configProvider,
            $this->variablesProcessorRegistry,
            $this->translation
        );
    }

    /**
     * @param string $content
     * @param string $subject
     *
     * @return EmailTemplateInterface
     */
    private function getEmailTemplate(string $content, string $subject = ''): EmailTemplateInterface
    {
        $emailTemplate = new EmailTemplateModel();
        $emailTemplate->setContent($content);
        $emailTemplate->setSubject($subject);

        return $emailTemplate;
    }

    /**
     * @param string $propertyName
     * @param string $path
     * @param string $parentPath
     *
     * @return string
     */
    private function getEntityVariableTemplate(string $propertyName, string $path, string $parentPath): string
    {
        return strtr(
            self::ENTITY_VARIABLE_TEMPLATE,
            [
                '%name%'   => $propertyName,
                '%val%'    => $path,
                '%parent%' => $parentPath
            ]
        );
    }

    /**
     * @param array $entityVariableProcessors
     * @param array $systemVariableValues
     */
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

    public function testCompilePreview()
    {
        $entity = new TestEntityForVariableProvider();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);

        $template = 'test <a href="http://example.com">test</a> {{ system.testVar }}';
        $expectedRenderedResult = '{% verbatim %}' . $template . '{% endverbatim %}';

        $emailTemplate = new EmailTemplateEntity();
        $emailTemplate->setContent($template);
        $emailTemplate->setSubject('');

        $this->environment->expects(self::once())
            ->method('render')
            ->with($expectedRenderedResult, self::identicalTo([]))
            ->willReturnArgument(0);

        self::assertSame(
            $expectedRenderedResult,
            $this->renderer->compilePreview($emailTemplate)
        );
    }

    public function testCompileMessage()
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
            . ' {{ entity.field1|oro_html_sanitize }}'
            . ' {{ entity.field2|trim|raw }}'
            . ' {{ func(entity.field3) }}'
            . ' {{ system.testVar }}'
            . ' N/A';

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [$entityClass => ['getField1']],
                'accessors'          => [$entityClass => ['field1' => 'getField1']],
                'default_formatters' => $defaultFormatters
            ]);
        $this->expectVariables($entityVariableProcessors, $systemVars);

        $emailTemplate = $this->getEmailTemplate($content, $subject);
        $templateParams = [
            'entity' => $entity,
            'system' => $systemVars
        ];

        $this->environment->expects(self::exactly(2))
            ->method('render')
            ->willReturnMap([
                [$subject, $templateParams, $subject],
                [$content, $templateParams, $content]
            ]);

        $result = $this->renderer->compileMessage($emailTemplate, $templateParams);

        self::assertInternalType('array', $result);
        self::assertCount(2, $result);
        self::assertSame($subject, $result[0]);
        self::assertSame($content, $result[1]);
    }

    public function testRenderTemplate()
    {
        $template = 'test '
            . "\n"
            . '{{ entity.field1 }}'
            . "\n"
            . '{{ system.currentDate }}';
        $expectedRenderedResult =
            'test '
            . "\n"
            . $this->getEntityVariableTemplate('field1', 'entity.field1', 'entity')
            . "\n"
            . '{{ system.currentDate }}';

        $entity = new TestEntityForVariableProvider();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity) => []
        ]);

        $this->environment->expects(self::any())
            ->method('render')
            ->willReturnArgument(0);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }
}
