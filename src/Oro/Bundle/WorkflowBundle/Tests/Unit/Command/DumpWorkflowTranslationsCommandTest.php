<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Oro\Bundle\WorkflowBundle\Command\DumpWorkflowTranslationsCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Command\Stub\TestOutput;

class DumpWorkflowTranslationsCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflow;

    /** @var InputInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $input;

    /** @var TestOutput */
    protected $output;

    /** @var DumpWorkflowTranslationsCommand */
    protected $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_workflow.manager', 1, $this->workflowManager],
                ['translator.default', 1, $this->translator],
            ]));

        $this->input = $this->getMock(InputInterface::class);
        $this->output = new TestOutput();

        $this->command = new DumpWorkflowTranslationsCommand();
        $this->command->setContainer($this->container);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->translator,
            $this->workflowManager,
            $this->workfow,
            $this->container,
            $this->input,
            $this->output,
            $this->command
        );
    }

    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('workflow'));
        $this->assertTrue($this->command->getDefinition()->hasArgument('locale'));
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

        $this->input->expects($this->exactly(2))
            ->method('getArgument')
            ->will($this->returnValueMap([
                ['locale', 'locale1'],
                ['workflow', 'workflow1'],
            ]));

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('workflow1')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $domain = DumpWorkflowTranslationsCommand::TRANSLATION_DOMAIN;
        $defaultLocale = Translation::DEFAULT_LOCALE;

        $this->translator->expects($this->any())
            ->method('hasTrans')
            ->will($this->returnValueMap([
                ['wf1.definition1.label', $domain, 'locale1', true],
                ['wf1.step1.label', $domain, 'locale1', true],
                ['wf1.step2.label', $domain, 'locale1', false],
                ['wf1.step2.label', $domain, $defaultLocale, true],
                ['wf1.step3.label', $domain, 'locale1', false],
                ['wf1.step3.label', $domain, $defaultLocale, false],
                ['wf1.attribute1.label', $domain, 'locale1', true],
                ['wf1.transition1.label', $domain, 'locale1', true],
                ['wf1.transition1.message', $domain, 'locale1', true],
            ]));

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnValueMap([
                ['wf1.definition1.label', [], $domain, 'locale1', 'definition1.label.locale1'],
                ['wf1.step1.label', [], $domain, 'locale1', 'step1.label.locale1'],
                ['wf1.step2.label', [], $domain, $defaultLocale, 'step2.label.defaultLocale'],
                ['wf1.attribute1.label', [], $domain, 'locale1', 'attribute1.label.locale1'],
                ['wf1.transition1.label', [], $domain, 'locale1', 'transition1.label.locale1'],
                ['wf1.transition1.message', [], $domain, 'locale1', 'transition1.message.locale1'],
            ]));


        $this->command->run($this->input, $this->output);

        $this->assertEquals(
            [
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
                ),
            ],
            $this->output->messages
        );
    }
}
