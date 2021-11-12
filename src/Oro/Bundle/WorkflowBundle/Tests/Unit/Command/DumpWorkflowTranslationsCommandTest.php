<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Oro\Bundle\WorkflowBundle\Command\DumpWorkflowTranslationsCommand;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DumpWorkflowTranslationsCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    private $workflow;

    /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $input;

    /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $output;

    /** @var DumpWorkflowTranslationsCommand */
    private $command;

    /** @var WorkflowTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowTranslationHelper;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->workflow = $this->createMock(Workflow::class);
        $this->workflowTranslationHelper = $this->createMock(WorkflowTranslationHelper::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new DumpWorkflowTranslationsCommand(
            $this->workflowManager,
            $this->workflowTranslationHelper
        );
    }

    public function testGetName()
    {
        $this->assertEquals(DumpWorkflowTranslationsCommand::getDefaultName(), $this->command->getName());
    }

    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('workflow'));
        $this->assertTrue($this->command->getDefinition()->hasOption('locale'));
    }

    public function testExecute()
    {
        $definition = (new WorkflowDefinition())
            ->setLabel('wf1.definition1.label')
            ->setConfiguration([
                'steps' => [
                    'step1' => [
                        'label' => 'wf1.step1.label',
                    ],
                    'step2' => [
                        'label' => 'wf1.step2.label',
                    ],
                    'step3' => [
                        'label' => 'wf1.step3.label',
                    ],
                ],
                'attributes' => [
                    'attribute1' => [
                        'label' => 'wf1.attribute1.label',
                    ],
                ],
                'transitions' => [
                    'transition1' => [
                        'label' => 'wf1.transition1.label',
                        'message' => 'wf1.transition1.message',
                    ],
                ],
            ]);

        $this->input->expects($this->once())
            ->method('getArgument')
            ->willReturnMap([
                ['workflow', 'workflow1'],
            ]);

        $this->input->expects($this->once())
            ->method('getOption')
            ->willReturnMap([
                ['locale', 'locale1'],
            ]);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('workflow1')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->workflowTranslationHelper->expects($this->any())
            ->method('generateDefinitionTranslationKeys')
            ->willReturn([
                'wf1.definition1.label',
                'wf1.step1.label',
                'wf1.step2.label',
                'wf1.step2.label',
                'wf1.step3.label',
                'wf1.step3.label',
                'wf1.attribute1.label',
                'wf1.transition1.label',
                'wf1.transition1.message',
            ]);

        $this->workflowTranslationHelper->expects($this->any())
            ->method('generateDefinitionTranslations')
            ->willReturn([
                'wf1.definition1.label'=>'definition1.label.locale1',
                'wf1.step1.label'=>'step1.label.locale1',
                'wf1.step2.label'=>'step2.label.defaultLocale',
                'wf1.step3.label'=>'',
                'wf1.attribute1.label'=>'attribute1.label.locale1',
                'wf1.transition1.label'=>'transition1.label.locale1',
                'wf1.transition1.message'=>'transition1.message.locale1',
            ]);

        $this->output->expects($this->once())
            ->method('write')
            ->with(
                Yaml::dump(
                    [
                        'wf1' => [
                            'definition1' => ['label' => 'definition1.label.locale1'],
                            'step1' => ['label' => 'step1.label.locale1'],
                            'step2' => ['label' => 'step2.label.defaultLocale'],
                            'step3' => ['label' => ''],
                            'attribute1' => ['label' => 'attribute1.label.locale1'],
                            'transition1' => [
                                'label' => 'transition1.label.locale1',
                                'message' => 'transition1.message.locale1',
                            ],
                        ],
                    ],
                    DumpWorkflowTranslationsCommand::INLINE_LEVEL
                )
            );

        $this->command->run($this->input, $this->output);
    }
}
