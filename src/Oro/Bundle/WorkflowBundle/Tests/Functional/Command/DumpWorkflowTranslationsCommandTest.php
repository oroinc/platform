<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\WorkflowBundle\Command\DumpWorkflowTranslationsCommand;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowTranslations;
use Symfony\Component\Yaml\Yaml;

class DumpWorkflowTranslationsCommandTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadWorkflowTranslations::class]);

        /* @var $provider WorkflowConfigFinderBuilder */
        $builder = $this->getContainer()->get('oro_workflow.configuration.workflow_config_finder.builder');
        $builder->setSubDirectory('/Tests/Functional/Command/DataFixtures/ValidDefinitions');

        $this->runCommand('oro:workflow:definitions:load');
    }

    public function testExecute()
    {
        $result = $this->runCommand(
            DumpWorkflowTranslationsCommand::NAME,
            [
                LoadWorkflowTranslations::WORKFLOW4,
                '--locale' => LoadLanguages::LANGUAGE2,
            ],
            false
        );

        $this->assertNotEmpty($result);

        $this->assertEquals(
            [
                'oro' => [
                    'workflow' => [
                        LoadWorkflowTranslations::WORKFLOW4 => [
                            'label' => 'workflow4.label.value',
                            'step' => [
                                'step1' => ['label' => 'workflow4.step1.label.value'],
                                'step2' => ['label' => 'workflow4.step2.label.default'],
                                'step3' => ['label' => ''],
                            ],
                            'attribute' => [
                                'attribute1' => ['label' => 'workflow4.attribute1.label.value'],
                                'attribute2' => ['label' => 'workflow4.attribute2.label.value'],
                            ],
                            'transition' => [
                                'transition1' => [
                                    'label' => 'workflow4.transition1.label.value',
                                    'warning_message' => 'workflow4.transition1.message.value',
                                    'button_label' => 'workflow4.transition1.button_label.value',
                                    'button_title' => 'workflow4.transition1.button_title.value',
                                ],
                                'transition2' => [
                                    'label' => 'workflow4.transition2.label.value',
                                    'warning_message' => 'workflow4.transition2.message.value',
                                    'button_label' => 'workflow4.transition2.button_label.value',
                                    'button_title' => 'workflow4.transition2.button_title.value',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            Yaml::parse($result)
        );
    }
}
