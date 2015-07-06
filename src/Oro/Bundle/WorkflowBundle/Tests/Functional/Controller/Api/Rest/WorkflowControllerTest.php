<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @dbIsolation
 * @dbReindex
 */
class WorkflowControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    protected $entityClass = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures(array('Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions'));
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManagerForClass($this->entityClass);
    }

    /**
     * @dataProvider deleteDataProvider
     */
    public function testDeleteAction($finalDefinitionHasStartStep)
    {
        $workflowManager = $this->getWorkflowManager();
        $repository      = $this->entityManager->getRepository('OroWorkflowBundle:WorkflowDefinition');

        $workflowDefinitionNoStartStep = $repository->find(LoadWorkflowDefinitions::NO_START_STEP);
        $workflowDefinitionStartStep   = $repository->find(LoadWorkflowDefinitions::WITH_START_STEP);

        $workflowFrom = $finalDefinitionHasStartStep ? $workflowDefinitionNoStartStep : $workflowDefinitionStartStep;
        $workflowTo   = $finalDefinitionHasStartStep ? $workflowDefinitionStartStep : $workflowDefinitionNoStartStep;
        $workflowFromName = $workflowFrom->getName();
        $workflowToName   = $workflowTo->getName();

        // activating workflow definition
        $workflowManager->activateWorkflow($workflowFrom);
        $this->assertActiveWorkflow($this->entityClass, $workflowFromName);

        // create new test entity
        $entity = $this->createNewEntity();
        if ($finalDefinitionHasStartStep) {
            $workflowManager->startWorkflow($workflowFromName, $entity, LoadWorkflowDefinitions::START_TRANSITION);
        }
        $this->assertEntityWorkflowItem($entity, $workflowFromName);

        // activating other workflow definition
        $workflowManager->activateWorkflow($workflowTo);
        $this->assertActiveWorkflow($this->entityClass, $workflowToName);

        // assert the current entity still has relation to the not active workflow definition
        $this->entityManager->refresh($entity);
        $this->assertEntityWorkflowItem($entity, $workflowFromName);

        // performing delete workflow item using API
        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_workflow_delete',
                array('workflowItemId' => $entity->getWorkflowItem()->getId())
            )
        );

        // check is deleting workflow item has been performed successfully
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->entityManager->refresh($entity);
        if ($finalDefinitionHasStartStep) {
            $this->assertEntityWorkflowItem($entity, $workflowToName);
        } else {
            $this->assertEntityWorkflowItem($entity, null);
        }
    }

    /**
     * @return array
     */
    public function deleteDataProvider()
    {
        return array(
            'final definition with start step' => array(true),
            'final definition without start step' => array(false),
        );
    }

    public function testDeactivateAndActivateActions()
    {
        // set and assert active workflow
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::WITH_START_STEP);

        // create and assert test entity
        $testEntity = $this->createNewEntity();
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        // deactivate workflow for entity
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_workflow_api_rest_workflow_deactivate',
                array('entityClass' => $this->entityClass)
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertActivationResult($result);
        $this->assertActiveWorkflow($this->entityClass, null);

        // assert that entity still has relation to workflow
        $this->entityManager->refresh($testEntity);
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        // activate other workflow through API
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_workflow_api_rest_workflow_activate',
                array('workflowDefinition' => LoadWorkflowDefinitions::NO_START_STEP)
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertActivationResult($result);
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::NO_START_STEP);

        // assert that entity workflow item was reset
        $this->entityManager->refresh($testEntity);
        $this->assertEntityWorkflowItem($testEntity, null);
    }

    /**
     * Tests transition
     * {@see \Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::transitAction}
     * {@see \Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::transitByEntityAction}
     *
     * @dataProvider transitActionProvider
     *
     * @param array $testCase
     */
    public function testTransitAction($testCase)
    {
        // set and assert active workflow
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::WITH_START_STEP);

        // create and assert test entity
        $testEntity = $this->createNewEntity();
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        // transit workflow item for entity
        $this->client->request(
            'GET',
            $this->getUrl(
                $testCase['urlName'],
                $testCase['params']($this->client, $testEntity)
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), $testCase['responseCode']);

        // assert result
        $testCase['assertResult']($this->entityManager, $result, $testEntity);
    }

    /**
     * Produce parameters for testing
     * \Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::transitAction and
     * \Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::transitByEntityAction
     *
     * @return array
     */
    public function transitActionProvider()
    {
        /** @noinspection PhpUnusedParameterInspection */
        /**
         * Default asserts (failed requests)
         *
         * @param EntityManager       $entityManager
         * @param array               $result WebTestCase::getJsonResponseContent result
         * @param WorkflowAwareEntity $testEntity
         *
         */
        $assertResult = function (EntityManager $entityManager, $result, WorkflowAwareEntity $testEntity) {
            // assert entity workflow step
            $entityManager->refresh($testEntity);
            $this->assertEquals($testEntity->getWorkflowStep()->getName(), LoadWorkflowDefinitions::START_STEP);
        };

        /**
         * Success transition result asserts
         *
         * @param EntityManager       $entityManager
         * @param array               $result WebTestCase::getJsonResponseContent result
         * @param WorkflowAwareEntity $testEntity
         */
        $assertSuccessResult = function (EntityManager $entityManager, $result, WorkflowAwareEntity $testEntity) {
            $this->assertArrayHasKey('workflowItem', $result);
            $this->assertEquals($result['workflowItem']['id'], $testEntity->getWorkflowItem()->getId());
            $this->assertEquals(
                $result['workflowItem']['workflow_name'],
                $testEntity->getWorkflowItem()->getWorkflowName()
            );
            $this->assertEquals($result['workflowItem']['entity_id'], $testEntity->getId());

            // assert entity workflow step
            $entityManager->refresh($testEntity);
            $this->assertEquals($testEntity->getWorkflowStep()->getName(), LoadWorkflowDefinitions::FINISH_STEP);
        };

        /** @noinspection PhpUnusedParameterInspection */
        /**
         * Default params for oro_workflow_api_rest_workflow_transit
         *
         * @param Client              $client
         * @param WorkflowAwareEntity $testEntity
         *
         * @return array
         */
        $params = function (Client $client, WorkflowAwareEntity $testEntity) {
            return [
                'workflowItemId' => $testEntity->getWorkflowItem()->getId(),
                'transitionName' => LoadWorkflowDefinitions::FINISH_TRANSITION,
            ];
        };

        /** @noinspection PhpUnusedParameterInspection */
        /**
         * Default params for oro_workflow_api_rest_workflow_transitbyentity
         *
         * @param Client              $client
         * @param WorkflowAwareEntity $testEntity
         *
         * @return array
         */
        $paramsByEntity = function (Client $client, WorkflowAwareEntity $testEntity) {
            return [
                'entityName'     => $this->entityClass,
                'entityId'       => $testEntity->getId(),
                'transitionName' => LoadWorkflowDefinitions::FINISH_TRANSITION,
            ];
        };


        return [
            // ----------------------------------------------------------------------
            // oro_workflow_api_rest_workflow_transitbyentity
            // ----------------------------------------------------------------------
            [
                'successful_transition' => [
                    'urlName'      => 'oro_workflow_api_rest_workflow_transitbyentity',
                    'params'       => $paramsByEntity,
                    'responseCode' => 200,
                    'assertResult' => $assertSuccessResult,
                ]
            ],
            [
                'url_safe_class_name' => [
                    'urlName'      => 'oro_workflow_api_rest_workflow_transitbyentity',
                    'params'       => function (Client $client, WorkflowAwareEntity $testEntity) use ($paramsByEntity) {
                        /** @var EntityRoutingHelper $routingHelper */
                        $routingHelper = $client->getContainer()->get('oro_entity.routing_helper');

                        return array_merge($paramsByEntity($client, $testEntity), [
                            'entityName' => $routingHelper->getUrlSafeClassName($this->entityClass),
                        ]);
                    },
                    'responseCode' => 200,
                    'assertResult' => $assertSuccessResult,
                ]
            ],
            [
                'class_does_not_exists' => [
                    'urlName'      => 'oro_workflow_api_rest_workflow_transitbyentity',
                    'params'       => function (Client $client, WorkflowAwareEntity $testEntity) use ($paramsByEntity) {
                        return array_merge($paramsByEntity($client, $testEntity), [
                            'entityName' => 'Does\Not\Exists\Class\Foo\Bar',
                        ]);
                    },
                    'responseCode' => 400,
                    'assertResult' => $assertResult,
                ]
            ],
            [
                'entity_does_not_exists' => [
                    'urlName'      => 'oro_workflow_api_rest_workflow_transitbyentity',
                    'params'       => function (Client $client, WorkflowAwareEntity $testEntity) use ($paramsByEntity) {
                        return array_merge($paramsByEntity($client, $testEntity), [
                            'entityId' => 0,
                        ]);
                    },
                    'responseCode' => 404,
                    'assertResult' => $assertResult,
                ]
            ],
            [
                'transition_does_not_exists' => [
                    'urlName'      => 'oro_workflow_api_rest_workflow_transitbyentity',
                    'params'       => function (Client $client, WorkflowAwareEntity $testEntity) use ($paramsByEntity) {
                        return array_merge($paramsByEntity($client, $testEntity), [
                            'transitionName' => 'test_does_not_exists_transition',
                        ]);
                    },
                    'responseCode' => 400,
                    'assertResult' => $assertResult,
                ]
            ],


            // ----------------------------------------------------------------------
            // oro_workflow_api_rest_workflow_transit
            // ----------------------------------------------------------------------
            [
                'successful_transition' => [
                    'urlName'      => 'oro_workflow_api_rest_workflow_transit',
                    'params'       => $params,
                    'responseCode' => 200,
                    'assertResult' => $assertSuccessResult,
                ]
            ],
            [
                'workflow_item_does_not_exists' => [
                    'urlName'      => 'oro_workflow_api_rest_workflow_transit',
                    'params'       => function (Client $client, WorkflowAwareEntity $testEntity) use ($params) {
                        return array_merge($params($client, $testEntity), [
                            'workflowItemId' => 0,
                        ]);
                    },
                    'responseCode' => 404,
                    'assertResult' => $assertResult,
                ]
            ],
            [
                'transition_does_not_exists' => [
                    'urlName'      => 'oro_workflow_api_rest_workflow_transit',
                    'params'       => function (Client $client, WorkflowAwareEntity $testEntity) use ($params) {
                        return array_merge($params($client, $testEntity), [
                            'transitionName' => 'test_does_not_exists_transition',
                        ]);
                    },
                    'responseCode' => 400,
                    'assertResult' => $assertResult,
                ]
            ],
        ];
    }

    protected function createNewEntity()
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid());
        $this->entityManager->persist($testEntity);
        $this->entityManager->flush($testEntity);

        return $testEntity;
    }

    /**
     * @return WorkflowManager
     */
    protected function getWorkflowManager()
    {
        return $this->client->getContainer()->get('oro_workflow.manager');
    }

    /**
     * @param array $result
     */
    protected function assertActivationResult($result)
    {
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['successful']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * @param WorkflowAwareEntity $entity
     * @param string|null $workflowName
     */
    protected function assertEntityWorkflowItem(WorkflowAwareEntity $entity, $workflowName)
    {
        if ($workflowName) {
            $this->assertNotEmpty($entity->getWorkflowItem());
            $this->assertEquals($workflowName, $entity->getWorkflowItem()->getWorkflowName());
        } else {
            $this->assertEmpty($entity->getWorkflowItem());
        }
    }

    /**
     * @param string
     * @param string|null $workflowName
     */
    protected function assertActiveWorkflow($entityClass, $workflowName)
    {
        if ($workflowName) {
            $activeWorkflow = $this->getWorkflowManager()->getApplicableWorkflowByEntityClass($entityClass);
            $this->assertNotEmpty($activeWorkflow);
            $this->assertEquals($workflowName, $activeWorkflow->getName());
        } else {
            $this->assertNull($this->getWorkflowManager()->getApplicableWorkflowByEntityClass($entityClass));
        }
    }
}
