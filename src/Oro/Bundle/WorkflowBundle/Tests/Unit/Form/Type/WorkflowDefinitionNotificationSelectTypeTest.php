<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionNotificationSelectType;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowDefinitionNotificationSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const WORKFLOW_NAME = 'test_workflow';

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowRegistry;

    /** @var WorkflowDefinitionNotificationSelectType */
    protected $type;

    /** @var array|WorkflowDefinition[] */
    protected $definitions = [];

    protected function setUp()
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);

        $this->type = new WorkflowDefinitionNotificationSelectType($this->workflowRegistry);
        parent::setUp();
    }

    public function testSubmit()
    {
        $workflows = $this->getWorkflows($this->getDefinitions());
        $workflow = reset($workflows);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflowsByEntityClass')
            ->willReturn($workflows);

        $form = $this->factory->create(
            WorkflowDefinitionNotificationSelectType::class,
            null,
            ['entityClass' => \stdClass::class]
        );

        $this->assertFormOptionEqual($this->getDefinitions(), 'choices', $form);
        $this->assertNull($form->getData());

        $form->submit($workflow->getName());

        $this->assertTrue($form->isValid());
        $this->assertEquals($workflow->getDefinition(), $form->getData());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefined')->with('entityClass');
        $resolver->expects($this->once())->method('setAllowedTypes')->with('entityClass', ['string']);
        $resolver->expects($this->once())->method('setNormalizer')->with('choices');

        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(Select2EntityType::class, $this->type->getParent());
    }

    /**
     * @dataProvider incorrectOptionsDataProvider
     *
     * @param array $options
     * @param string $exceptionMessage
     *
     * @expectedException \InvalidArgumentException
     */
    public function testNormalizersException(array $options, $exceptionMessage)
    {
        $this->expectExceptionMessage($exceptionMessage);

        $this->factory->create(WorkflowDefinitionNotificationSelectType::class, null, $options);
    }

    /**
     * @return array
     */
    public function incorrectOptionsDataProvider()
    {
        return [
            'empty options' => [
                'options' => [],
                'exceptionMessage' => 'The required option "entityClass" is missing',
            ],
            'wrong options' => [
                'options' => ['entityClass' => new \stdClass()],
                'exceptionMessage' => 'The option "entityClass" with value stdClass is expected to be of type ' .
                    '"string", but is of type "stdClass"',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $entityType = new EntityTypeStub($this->getDefinitions(), 'oro_select2_entity');

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    EntityType::class => $entityType
                ],
                []
            )
        ];
    }

    /**
     * @return WorkflowDefinition[]
     */
    private function getDefinitions()
    {
        if (!$this->definitions) {
            $this->definitions = [
                self::WORKFLOW_NAME => $this->getEntity(
                    'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                    ['name' => self::WORKFLOW_NAME, 'label' => 'workflow_label']
                ),
                'other_workflow_name' => $this->getEntity(
                    'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                    ['name' => 'other_workflow_name', 'label' => 'other_workflow_label']
                )
            ];
        }

        return $this->definitions;
    }

    /**
     * @param WorkflowDefinition[] $definitions
     * @return Workflow[]
     */
    private function getWorkflows(array $definitions)
    {
        $workflows = [];
        foreach ($definitions as $definition) {
            $workflow = $this->createMock(Workflow::class);
            $workflow->expects($this->any())->method('getName')->willReturn($definition->getName());
            $workflow->expects($this->any())->method('getLabel')->willReturn($definition->getLabel());
            $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);
            $workflows[] = $workflow;
        }

        return $workflows;
    }
}
