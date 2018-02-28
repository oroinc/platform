<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Form\Type\WorkflowAwareEntityType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithFormConfiguration;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @dbIsolationPerTest
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
        $this->loadFixtures([
            LoadWorkflowDefinitions::class,
            LoadWorkflowDefinitionsWithFormConfiguration::class,
        ]);
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManagerForClass(self::ENTITY_CLASS);
        $this->workflowManager = $this->client->getContainer()->get('oro_workflow.manager');
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitions::MULTISTEP);
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitions::WITH_INIT_OPTION);
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME);
        $this->entity = $this->createNewEntity();
    }

    public function testEntityWorkflowsAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_entity_workflows', [
                '_widgetContainer' => 'dialog',
                'entityClass' => self::ENTITY_CLASS,
                'entityId' => $this->entity->getId()
            ])
        );

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());
        $this->assertContains('transition-test_multistep_flow-starting_point_transition', $crawler->html());
        $this->assertContains('transition-test_start_step_flow-start_transition', $crawler->html());
        $this->assertContains($this->getStepLabel('test_active_flow1', 'step1'), $crawler->html());
        $this->assertContains($this->getStepLabel('test_active_flow2', 'step1'), $crawler->html());
        $this->assertContains($this->getStepLabel('test_start_step_flow', 'open'), $crawler->html());
        $this->assertContains($this->getStepLabel('test_multistep_flow', 'starting_point'), $crawler->html());
        $this->assertContains(
            $this->getStepLabel(
                LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME,
                LoadWorkflowDefinitionsWithFormConfiguration::WFC_STEP_NAME
            ),
            $crawler->html()
        );
        $this->assertContains(
            $this->getTransitionLabel(
                LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME,
                LoadWorkflowDefinitionsWithFormConfiguration::WFC_START_TRANSITION,
                true
            ),
            $crawler->html()
        );
    }

    /**
     * @param string $workflowName
     * @param string $stepName
     *
     * @return string
     */
    protected function getStepLabel($workflowName, $stepName)
    {
        return sprintf('%s.%s.step.%s.label', WorkflowTemplate::KEY_PREFIX, $workflowName, $stepName);
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @param bool $useTransitionLabel
     *
     * @return string
     */
    protected function getTransitionLabel($workflowName, $transitionName, $useTransitionLabel = false)
    {
        return sprintf(
            $useTransitionLabel ? '%s.%s.transition.%s.label' : '%s.%s.transition.%s.button_label',
            WorkflowTemplate::KEY_PREFIX,
            $workflowName,
            $transitionName
        );
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
            ['entityId' => $this->entity->getId()]
        );

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());
        $this->assertTransitionFromSubmit($crawler, $workflowItem);
    }

    public function testStartTransitionFormActionFromNonRelatedEntity()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_start_transition_form', [
                '_widgetContainer' => 'dialog',
                'workflowName' => LoadWorkflowDefinitions::WITH_INIT_OPTION,
                'transitionName' => LoadWorkflowDefinitions::START_FROM_ROUTE_TRANSITION_WITH_FORM,
            ]),
            ['entityClass' => 'class1', 'route' => 'route1']
        );

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());

        $form = $crawler->selectButton('Submit')->form();
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(LoadWorkflowDefinitions::WITH_INIT_OPTION, $crawler->html());
    }

    public function testStartTransitionFormWithConfigurationAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_start_transition_form', [
                '_widgetContainer' => 'dialog',
                'workflowName' => LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME,
                'transitionName' => LoadWorkflowDefinitionsWithFormConfiguration::WFC_START_TRANSITION,
            ]),
            ['entityId' => $this->entity->getId()]
        );

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());

        $workflowItem = new WorkflowItem();
        $workflowItem->setEntityId($this->entity->getId())
            ->setWorkflowName(LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME);

        $this->assertTransitionFromWithConfigurationSubmit($crawler, $workflowItem, 'data_value_one', [
            WorkflowAwareEntityType::NAME => ['name' => 'name']
        ]);
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
            ])
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());
        $this->assertTransitionFromSubmit($crawler, $workflowItem);
    }

    public function testTransitionFormWithConfigurationAction()
    {
        $this->getContainer()->get('oro_workflow.manager')->startWorkflow(
            LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME,
            $this->entity,
            LoadWorkflowDefinitionsWithFormConfiguration::WFC_START_TRANSITION
        );

        $workflowItem = $this->getWorkflowItem(
            $this->entity,
            LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_transition_form', [
                '_widgetContainer' => 'dialog',
                'workflowItemId' => $workflowItem->getId(),
                'transitionName' => LoadWorkflowDefinitionsWithFormConfiguration::WFC_TRANSITION,
            ])
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());
        $this->assertTransitionFromWithConfigurationSubmit($crawler, $workflowItem, 'data_value_one', [
            WorkflowAwareEntityType::NAME => ['name' => 'name']
        ]);
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

    /**
     * @param Crawler $crawler
     * @param WorkflowItem $workflowItem
     * @param string $dataAttribute
     * @param array $data
     */
    protected function assertTransitionFromWithConfigurationSubmit(
        Crawler $crawler,
        WorkflowItem $workflowItem,
        $dataAttribute,
        array $data = []
    ) {
        $form = $crawler->selectButton('Save')->form($data);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        if ($workflowItem->getId()) {
            $this->assertContains(sprintf('"id":%d', $workflowItem->getId()), $crawler->html());
        }
        $this->assertContains(sprintf('"workflow_name":"%s"', $workflowItem->getWorkflowName()), $crawler->html());
        $this->assertContains(sprintf('"entity_id":"%s"', $workflowItem->getEntityId()), $crawler->html());

        $workflowItemNew = $this->getWorkflowItem($this->entity, $workflowItem->getWorkflowName());

        if ($workflowItem->getId()) {
            $this->assertSame($workflowItem->getId(), $workflowItemNew->getId());
        }
        $this->assertInstanceOf(WorkflowAwareEntity::class, $workflowItemNew->getData()->get($dataAttribute));
    }

    public function testButtonsAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_buttons', [
                '_widgetContainer' => 'dialog',
                'entityClass' => self::ENTITY_CLASS,
                'entityId' => $this->entity->getId(),
            ])
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $transitionButton = $crawler->selectLink('oro.workflow.test_active_flow1.transition.transition1.button_label');
        $this->assertCount(1, $transitionButton);
        $this->assertSame('javascript:void(0);', $transitionButton->attr('href'));
        $this->assertContains('transition-test_multistep_flow-starting_point_transition', $crawler->html());
        $this->assertContains('transition-test_start_step_flow-start_transition', $crawler->html());
        $this->assertContains('transition-test_start_init_option-start_transition', $crawler->html());
        $this->assertContains($this->getTransitionLabel(
            LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME,
            LoadWorkflowDefinitionsWithFormConfiguration::WFC_START_TRANSITION
        ), $crawler->html());
        $this->assertNotContains('transition-test_start_init_option-start_transition_from_entities', $crawler->html());
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
