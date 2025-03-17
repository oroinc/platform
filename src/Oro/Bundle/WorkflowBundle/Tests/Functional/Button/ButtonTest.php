<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Button;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolationPerTest
 */
class ButtonTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWorkflowDefinitions::class]);

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->client->getContainer()->get('oro_workflow.manager');
        $workflowManager->activateWorkflow(LoadWorkflowDefinitions::MULTISTEP);
        $workflowManager->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);
        $workflowManager->activateWorkflow(LoadWorkflowDefinitions::WITH_INIT_OPTION);
        $workflowManager->activateWorkflow(LoadWorkflowDefinitions::WITH_DATAGRIDS);
    }

    private function createNewEntity(): WorkflowAwareEntity
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid('test', true));
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $entityManager->persist($testEntity);
        $entityManager->flush();

        return $testEntity;
    }

    /**
     * @dataProvider displayButtonsDataProvider
     */
    public function testDisplayButtons(array $context, array $expected = []): void
    {
        $allItems = [
            'transition-test_start_init_option-start_transition_from_routes"',
            'transition-test_start_init_option-start_transition_from_datagrids"',
            'transition-test_start_step_flow-start_transition"',
            'transition-test_start_init_option-start_transition"',
            'transition-test_start_init_option-transition_for_datagrid"',
            'transition-test_flow_datagrids-transition1"',
        ];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_buttons',
                array_merge(
                    ['_widgetContainer' => 'dialog', 'entityId' => $this->createNewEntity()->getId()],
                    $context
                )
            ),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, 200);
        if (0 === count($expected)) {
            self::assertEmpty($response->getContent());
            $notExpected = [];
        } else {
            $notExpected = array_diff($allItems, $expected);
        }
        foreach ($expected as $item) {
            self::assertStringContainsString($item, $crawler->html());
        }
        foreach ($notExpected as $item) {
            self::assertStringNotContainsString($item, $crawler->html());
        }
    }

    public function displayButtonsDataProvider(): array
    {
        return [
            'empty context' => [
                'context' => [],
                'expected' => [],
            ],
            'entity context' => [
                'context' => ['entityClass' => 'entity1'],
                'expected' => [
                    'transition-test_start_init_option-start_transition_from_entities',
                ],
            ],
            'route context' => [
                'context' => ['route' => 'route1'],
                'expected' => [
                    'transition-test_start_init_option-start_transition_from_routes',
                ],
            ],
            'datagrid context' => [
                'context' => ['datagrid' => 'datagrid1'],
                'expected' => [
                    'transition-test_start_init_option-start_transition_from_datagrids',
                ],
            ],
            'entity and datagrid context' => [
                'context' => ['datagrid' => 'datagrid1', 'entityClass' => 'entity1'],
                'expected' => [
                    'transition-test_start_init_option-start_transition_from_datagrids',
                    'transition-test_start_init_option-start_transition_from_entities',
                ],
            ],
            'workflow datagrid context' => [
                'context' => ['entityClass' => WorkflowAwareEntity::class, 'datagrid' => 'datagrid1'],
                'expected' => [
                    'transition-test_flow_datagrids-transition1',
                    'transition-test_start_init_option-start_transition_from_datagrids',
                ],
            ],
            'not matched context' => [
                'context' => ['entityClass' => 'some_other_entity_class'],
                'expected' => [],
            ],
        ];
    }
}
