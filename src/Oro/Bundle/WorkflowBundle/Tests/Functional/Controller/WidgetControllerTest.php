<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
class WidgetControllerTest extends WebTestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var WorkflowManager
     */
    private $workflowManager;

    /**
     * @var WorkflowAwareEntity
     */
    private $entity;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions']);
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManagerForClass(self::ENTITY_CLASS);
        $this->workflowManager = $this->client->getContainer()->get('oro_workflow.manager');
        $this->entity = $this->createNewEntity();
    }

    public function testStepsAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_steps', [
                '_widgetContainer' => 'dialog',
                'entityClass' => self::ENTITY_CLASS,
                'entityId' => $this->entity->getId()
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $this->assertNotEmpty($crawler->html());
        $this->assertContains('stepsData', $crawler->html());
        $this->assertContains(LoadWorkflowDefinitions::MULTISTEP, $crawler->html());
        $this->assertContains(LoadWorkflowDefinitions::WITH_START_STEP, $crawler->html());
    }

    public function testStartTransitionFormAction()
    {
        $workflowItem = $this->getWorkflowItem($this->entity, LoadWorkflowDefinitions::MULTISTEP);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_start_transition_form', [
                '_widgetContainer' => 'dialog',
                'workflowName' => LoadWorkflowDefinitions::MULTISTEP,
                'transitionName' => LoadWorkflowDefinitions::MULTISTEP_START_TRANSITION,
            ]),
            ['entityId' => $this->entity->getId(),],
            [],
            $this->generateBasicAuthHeader()
        );

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());
        $this->assertTransitionFromSubmit($crawler, $workflowItem);
    }

    public function testTransitionFormAction()
    {
        $workflowItem = $this->getWorkflowItem($this->entity, LoadWorkflowDefinitions::MULTISTEP);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_transition_form', [
                '_widgetContainer' => 'dialog',
                'workflowItemId' => $workflowItem->getId(),
                'transitionName' => LoadWorkflowDefinitions::MULTISTEP_START_TRANSITION,
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());
        $this->assertTransitionFromSubmit($crawler, $workflowItem);
    }

    /**
     * @param Crawler $crawler
     * @param WorkflowItem $workflowItem
     */
    protected function assertTransitionFromSubmit(Crawler $crawler, WorkflowItem $workflowItem)
    {
        $form = $crawler->selectButton('Submit')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains((string)$workflowItem->getId(), $crawler->html());
        $this->assertContains($workflowItem->getWorkflowName(), $crawler->html());
        $this->assertContains((string)$workflowItem->getEntityId() . '', $crawler->html());

        $workflowItemNew = $this->getWorkflowItem($this->entity, LoadWorkflowDefinitions::MULTISTEP);

        $this->assertNotEquals(
            $workflowItem->getCurrentStep()->getName(),
            $workflowItemNew->getCurrentStep()->getName()
        );
        $this->assertEquals('second_point', $workflowItemNew->getCurrentStep()->getName());
    }

    public function testButtonsAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_buttons', [
                '_widgetContainer' => 'dialog',
                'entityClass' => self::ENTITY_CLASS,
                'entityId' => $this->entity->getId(),
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertContains('transition-test_multistep_flow-starting_point_transition', $crawler->html());
        $this->assertContains('transition-test_start_step_flow-start_transition', $crawler->html());
    }

    /**
     * @return WorkflowAwareEntity
     */
    protected function createNewEntity()
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid('test', true));
        $this->entityManager->persist($testEntity);
        $this->entityManager->flush($testEntity);

        return $testEntity;
    }

    /**
     * @param WorkflowAwareEntity $entity
     * @param $workflowName
     *
     * @return null|WorkflowItem
     */
    protected function getWorkflowItem(WorkflowAwareEntity $entity, $workflowName)
    {
        return $this->getContainer()
            ->get('oro_workflow.manager')
            ->getWorkflowItem($entity, $workflowName);
    }
}
