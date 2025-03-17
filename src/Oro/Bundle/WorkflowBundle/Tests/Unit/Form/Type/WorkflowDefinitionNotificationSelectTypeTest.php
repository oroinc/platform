<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionNotificationSelectType;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowDefinitionNotificationSelectTypeTest extends FormIntegrationTestCase
{
    private const WORKFLOW_NAME = 'test_workflow';

    private WorkflowRegistry&MockObject $workflowRegistry;
    private WorkflowDefinitionNotificationSelectType $type;

    /** @var WorkflowDefinition[] */
    private array $definitions = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);

        $this->type = new WorkflowDefinitionNotificationSelectType($this->workflowRegistry);

        parent::setUp();
    }

    public function testSubmit(): void
    {
        $workflows = $this->getWorkflows($this->getDefinitions());
        $workflow = reset($workflows);

        $this->workflowRegistry->expects(self::once())
            ->method('getWorkflowsByEntityClass')
            ->willReturn(new ArrayCollection($workflows));

        $form = $this->factory->create(
            WorkflowDefinitionNotificationSelectType::class,
            null,
            ['entityClass' => \stdClass::class]
        );

        $this->assertFormOptionEqual($this->getDefinitions(), 'choices', $form);
        self::assertNull($form->getData());

        $form->submit($workflow->getName());

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($workflow->getDefinition(), $form->getData());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefined')
            ->with('entityClass');
        $resolver->expects(self::once())
            ->method('setAllowedTypes')
            ->with('entityClass', ['string']);
        $resolver->expects(self::once())
            ->method('setNormalizer')
            ->with('choices');

        $this->type->configureOptions($resolver);
    }

    public function testGetParent(): void
    {
        self::assertEquals(Select2EntityType::class, $this->type->getParent());
    }

    /**
     * @dataProvider incorrectOptionsDataProvider
     */
    public function testNormalizersException(array $options, string $exceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->factory->create(WorkflowDefinitionNotificationSelectType::class, null, $options);
    }

    public function incorrectOptionsDataProvider(): array
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

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    EntityType::class => new EntityTypeStub($this->getDefinitions())
                ],
                []
            )
        ];
    }

    /**
     * @return WorkflowDefinition[]
     */
    private function getDefinitions(): array
    {
        if (!$this->definitions) {
            $workflowDefinition = new WorkflowDefinition();
            $workflowDefinition->setName(self::WORKFLOW_NAME);
            $workflowDefinition->setLabel('workflow_label');
            $otherWorkflowDefinition = new WorkflowDefinition();
            $otherWorkflowDefinition->setName('other_workflow_name');
            $otherWorkflowDefinition->setLabel('other_workflow_label');
            $this->definitions = [
                self::WORKFLOW_NAME => $workflowDefinition,
                'other_workflow_name' => $otherWorkflowDefinition
            ];
        }

        return $this->definitions;
    }

    /**
     * @param WorkflowDefinition[] $definitions
     *
     * @return Workflow[]
     */
    private function getWorkflows(array $definitions): array
    {
        $workflows = [];
        foreach ($definitions as $definition) {
            $workflow = $this->createMock(Workflow::class);
            $workflow->expects(self::any())
                ->method('getName')
                ->willReturn($definition->getName());
            $workflow->expects(self::any())
                ->method('getLabel')
                ->willReturn($definition->getLabel());
            $workflow->expects(self::any())
                ->method('getDefinition')
                ->willReturn($definition);
            $workflows[] = $workflow;
        }

        return $workflows;
    }
}
