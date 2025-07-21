<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Oro\Bundle\WorkflowBundle\Command\DumpWorkflowTranslationsCommand;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DumpWorkflowTranslationsCommandTest extends TestCase
{
    private WorkflowManager&MockObject $workflowManager;
    private InputInterface&MockObject $input;
    private OutputInterface&MockObject $output;
    private WorkflowTranslationHelper&MockObject $workflowTranslationHelper;
    private DumpWorkflowTranslationsCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->workflowTranslationHelper = $this->createMock(WorkflowTranslationHelper::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new DumpWorkflowTranslationsCommand(
            $this->workflowManager,
            $this->workflowTranslationHelper
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro:workflow:translations:dump', $this->command->getName());
    }

    public function testConfigure(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('workflow'));
        $this->assertTrue($this->command->getDefinition()->hasOption('locale'));
    }

    public function testExecute(): void
    {
        $definition = $this->createMock(WorkflowDefinition::class);
        $this->input->expects($this->once())
            ->method('getArgument')
            ->willReturnMap([
                ['workflow', 'workflow1'],
            ]);

        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['locale', 'locale1'],
                ['parent-workflow', null],
            ]);

        $workflow = $this->createMock(Workflow::class);
        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('workflow1')
            ->willReturn($workflow);

        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->workflowTranslationHelper->expects($this->any())
            ->method('generateDefinitionTranslationKeys')
            ->willReturn([
                'oro.workflows.workflow1.definition1.label',
                'oro.workflows.workflow1.step1.label',
                'oro.workflows.workflow1.step2.label',
                'oro.workflows.workflow1.step3.label',
                'oro.workflows.workflow1.attribute1.label',
                'oro.workflows.workflow1.transition1.label',
                'oro.workflows.workflow1.transition1.message',
            ]);

        $this->workflowTranslationHelper->expects($this->any())
            ->method('generateDefinitionTranslations')
            ->willReturn([
                'oro.workflows.workflow1.definition1.label' => 'definition1.label.locale1',
                'oro.workflows.workflow1.step1.label' => 'step1.label.locale1',
                'oro.workflows.workflow1.step2.label' => 'step2.label.defaultLocale',
                'oro.workflows.workflow1.step3.label' => '',
                'oro.workflows.workflow1.attribute1.label' => 'attribute1.label.locale1',
                'oro.workflows.workflow1.transition1.label' => 'transition1.label.locale1',
                'oro.workflows.workflow1.transition1.message' => 'transition1.message.locale1',
            ]);

        $this->output->expects($this->once())
            ->method('write')
            ->with(
                Yaml::dump(
                    [
                        'oro' => [
                            'workflows' => [
                                'workflow1' => [
                                    'definition1' => ['label' => 'definition1.label.locale1'],
                                    'step1' => ['label' => 'step1.label.locale1'],
                                    'step2' => ['label' => 'step2.label.defaultLocale'],
                                    'step3' => ['label' => ''],
                                    'attribute1' => ['label' => 'attribute1.label.locale1'],
                                    'transition1' => [
                                        'label' => 'transition1.label.locale1',
                                        'message' => 'transition1.message.locale1',
                                    ],
                                ]
                            ]
                        ]
                    ],
                    DumpWorkflowTranslationsCommand::INLINE_LEVEL
                )
            );

        $this->command->run($this->input, $this->output);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithParentWorkflow(): void
    {
        $this->input->expects($this->once())
            ->method('getArgument')
            ->willReturnMap([
                ['workflow', 'workflow1'],
            ]);

        $locale = 'locale1';
        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['locale', $locale],
                ['parent-workflow', 'workflow_base'],
            ]);

        $definition = new WorkflowDefinition();
        $definition->setName('workflow1');
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $baseDefinition = new WorkflowDefinition();
        $baseDefinition->setName('workflow_base');
        $parentWorkflow = $this->createMock(Workflow::class);
        $parentWorkflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($baseDefinition);
        $this->workflowManager->expects($this->any())
            ->method('getWorkflow')
            ->willReturnMap([
                ['workflow1', $workflow],
                ['workflow_base', $parentWorkflow],
            ]);

        $workflowKeys = [
            'oro.workflows.workflow1.definition1.label',
            'oro.workflows.workflow1.step1.label',
            'oro.workflows.workflow1.step2.label',
            'oro.workflows.workflow1.step3.label',
            'oro.workflows.workflow1.attribute1.label',
            'oro.workflows.workflow1.transition1.label',
            'oro.workflows.workflow1.transition1.message'
        ];
        $parentWorkflowKeys = [
            'oro.workflows.workflow_base.definition1.label',
            'oro.workflows.workflow_base.step1.label',
            'oro.workflows.workflow_base.step2.label',
            'oro.workflows.workflow_base.step3.label',
            'oro.workflows.workflow_base.attribute1.label',
            'oro.workflows.workflow_base.transition1.label',
            'oro.workflows.workflow_base.transition1.message'
        ];
        $this->workflowTranslationHelper->expects($this->exactly(2))
            ->method('generateDefinitionTranslationKeys')
            ->willReturnMap([
                [$definition, $workflowKeys],
                [$baseDefinition, $parentWorkflowKeys]
            ]);

        $this->workflowTranslationHelper->expects($this->exactly(2))
            ->method('generateDefinitionTranslations')
            ->willReturnMap([
                [
                    $workflowKeys,
                    $locale,
                    '',
                    [
                        'oro.workflows.workflow1.definition1.label' => 'WF1 Definition Name',
                        'oro.workflows.workflow1.step1.label' => 'WF1 Step 1',
                        'oro.workflows.workflow1.step2.label' => '',
                        'oro.workflows.workflow1.step3.label' => '',
                        'oro.workflows.workflow1.attribute1.label' => '',
                        'oro.workflows.workflow1.transition1.label' => '',
                        'oro.workflows.workflow1.transition1.message' => 'WF1 Transition Message',
                    ]
                ],
                [
                    // Only keys for non-empty values from workflow1 are here
                    [
                        'oro.workflows.workflow_base.step2.label',
                        'oro.workflows.workflow_base.step3.label',
                        'oro.workflows.workflow_base.attribute1.label',
                        'oro.workflows.workflow_base.transition1.label',
                    ],
                    $locale,
                    '',
                    [
                        'oro.workflows.workflow_base.step2.label' => 'BaseWF Step 2',
                        'oro.workflows.workflow_base.step3.label' => '',
                        'oro.workflows.workflow_base.attribute1.label' => 'BaseWF Attribute 1',
                        'oro.workflows.workflow_base.transition1.label' => 'BaseWF Transition Label',
                    ]
                ]
            ]);

        $this->output->expects($this->once())
            ->method('write')
            ->with(
                Yaml::dump(
                    [
                        'oro' => [
                            'workflows' => [
                                'workflow1' => [
                                    'definition1' => ['label' => 'WF1 Definition Name'],
                                    'step1' => ['label' => 'WF1 Step 1'],
                                    'step2' => ['label' => 'BaseWF Step 2'],
                                    'step3' => ['label' => ''],
                                    'attribute1' => ['label' => 'BaseWF Attribute 1'],
                                    'transition1' => [
                                        'label' => 'BaseWF Transition Label',
                                        'message' => 'WF1 Transition Message',
                                    ],
                                ]
                            ]
                        ]
                    ],
                    DumpWorkflowTranslationsCommand::INLINE_LEVEL
                )
            );

        $this->command->run($this->input, $this->output);
    }
}
