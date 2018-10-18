<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Command\DumpWorkflowTranslationsCommand;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class DumpWorkflowTranslationsCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManager;

    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflow;

    /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $input;

    /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $output;

    /** @var DumpWorkflowTranslationsCommand */
    protected $command;

    /** @var WorkflowTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowTranslationHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowTranslationHelper = $this->createMock(WorkflowTranslationHelper::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_workflow.manager', 1, $this->workflowManager],
                ['oro_workflow.helper.translation', 1, $this->workflowTranslationHelper],
            ]));

        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new DumpWorkflowTranslationsCommand();
        $this->command->setContainer($this->container);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->workflowManager,
            $this->workfow,
            $this->container,
            $this->input,
            $this->output,
            $this->command,
            $this->workflowTranslationHelper
        );
    }

    public function testGetName()
    {
        $this->assertEquals(DumpWorkflowTranslationsCommand::NAME, $this->command->getName());
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

        $this->input->expects($this->exactly(1))
            ->method('getArgument')
            ->will($this->returnValueMap([
                ['workflow', 'workflow1'],
            ]));

        $this->input->expects($this->exactly(1))
            ->method('getOption')
            ->will($this->returnValueMap([
                ['locale', 'locale1'],
            ]));

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('workflow1')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())->method('getDefinition')->willReturn($definition);

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
