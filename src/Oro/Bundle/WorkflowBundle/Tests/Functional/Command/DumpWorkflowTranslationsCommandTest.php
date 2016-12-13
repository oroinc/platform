<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\WorkflowBundle\Command\DumpWorkflowTranslationsCommand;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowTranslations;

/**
 * @dbIsolation
 */
class DumpWorkflowTranslationsCommandTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadWorkflowTranslations::class]);

        /* @var $provider WorkflowConfigurationProvider */
        $provider = $this->getContainer()->get('oro_workflow.configuration.provider.workflow_config');

        $reflectionClass = new \ReflectionClass(WorkflowConfigurationProvider::class);

        $reflectionProperty = $reflectionClass->getProperty('configDirectory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($provider, '/Tests/Functional/Command/DataFixtures/ValidDefinitions');

        $this->runCommand('oro:workflow:definitions:load', ['--no-ansi']);
    }

    public function testExecute()
    {
        $result = $this->runCommand(
            DumpWorkflowTranslationsCommand::NAME,
            [
                LoadWorkflowTranslations::WORKFLOW4,
                '--locale' => LoadLanguages::LANGUAGE2,
                '--no-ansi'
            ]
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
                                ],
                                'transition2' => [
                                    'label' => 'workflow4.transition2.label.value',
                                    'warning_message' => 'workflow4.transition2.message.value',
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
