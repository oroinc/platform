<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowSelectTypeTest extends FormIntegrationTestCase
{
    private const TEST_ENTITY_CLASS = 'Test\Entity\Class';
    private const TEST_WORKFLOW_NAME = 'test_workflow_name';
    private const TEST_WORKFLOW_LABEL = 'Test Workflow Label';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var WorkflowSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->type = new WorkflowSelectType($this->registry, $this->translator);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], [])
        ];
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $inputOptions, array $expectedOptions)
    {
        $testWorkflowDefinition = new WorkflowDefinition();
        $testWorkflowDefinition->setName(self::TEST_WORKFLOW_NAME)
            ->setLabel(self::TEST_WORKFLOW_LABEL);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->any())
            ->method('findBy')
            ->with(['relatedEntity' => self::TEST_ENTITY_CLASS])
            ->willReturn([$testWorkflowDefinition]);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($repository);

        $form = $this->factory->create(WorkflowSelectType::class, null, $inputOptions);

        $actualOptions = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $actualOptions);
            $this->assertEquals($expectedValue, $actualOptions[$name]);
        }
    }

    public function configureOptionsDataProvider(): array
    {
        return [
            'no additional data' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'entity_class' => null,
                    'choices' => [],
                ]
            ],
            'custom choices' => [
                'inputOptions' => [
                    'choices' => ['key' => 'value']
                ],
                'expectedOptions' => [
                    'choices' => ['key' => 'value'],
                ]
            ],
            'custom entity class' => [
                'inputOptions' => [
                    'entity_class' => self::TEST_ENTITY_CLASS,
                ],
                'expectedOptions' => [
                    'entity_class' => self::TEST_ENTITY_CLASS,
                    'choices' => [self::TEST_WORKFLOW_LABEL => self::TEST_WORKFLOW_NAME],
                ]
            ],
            'parent configuration id' => [
                'inputOptions' => [
                    'config_id' => new EntityConfigId('test', self::TEST_ENTITY_CLASS),
                ],
                'expectedOptions' => [
                    'choices' => [self::TEST_WORKFLOW_LABEL => self::TEST_WORKFLOW_NAME],
                ]
            ],
        ];
    }

    public function testFinishView()
    {
        $label = 'test_label';
        $translatedLabel = 'translated_test_label';

        $view = new FormView();
        $view->vars['choices'] = [new ChoiceView([], 'test', $label)];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($label, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturn($translatedLabel);

        $this->type->finishView($view, $this->createMock(FormInterface::class), []);

        $this->assertEquals([new ChoiceView([], 'test', $translatedLabel)], $view->vars['choices']);
    }
}
