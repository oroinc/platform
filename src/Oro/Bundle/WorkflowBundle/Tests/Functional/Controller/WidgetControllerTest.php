<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

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
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var WorkflowAwareEntity */
    private $entity;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadWorkflowDefinitions::class,
            LoadWorkflowDefinitionsWithFormConfiguration::class,
        ]);

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
                'entityClass' => WorkflowAwareEntity::class,
                'entityId' => $this->entity->getId()
            ])
        );

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertNotEmpty($crawler->html());
        self::assertStringContainsString(
            'transition-test_multistep_flow-starting_point_transition',
            $crawler->html()
        );
        self::assertStringContainsString('transition-test_start_step_flow-start_transition', $crawler->html());
        self::assertStringContainsString($this->getStepLabel('test_active_flow1', 'step1'), $crawler->html());
        self::assertStringContainsString($this->getStepLabel('test_active_flow2', 'step1'), $crawler->html());
        self::assertStringContainsString($this->getStepLabel('test_start_step_flow', 'open'), $crawler->html());
        self::assertStringContainsString(
            $this->getStepLabel('test_multistep_flow', 'starting_point'),
            $crawler->html()
        );
        self::assertStringContainsString(
            $this->getStepLabel(
                LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME,
                LoadWorkflowDefinitionsWithFormConfiguration::WFC_STEP_NAME
            ),
            $crawler->html()
        );
        self::assertStringContainsString(
            $this->getTransitionLabel(
                LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME,
                LoadWorkflowDefinitionsWithFormConfiguration::WFC_START_TRANSITION,
                true
            ),
            $crawler->html()
        );
    }

    private function getStepLabel(string $workflowName, string $stepName): string
    {
        return sprintf('%s.%s.step.%s.label', WorkflowTemplate::KEY_PREFIX, $workflowName, $stepName);
    }

    private function getTransitionLabel(
        string $workflowName,
        string $transitionName,
        bool $useTransitionLabel = false
    ): string {
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
        self::assertStringContainsString(LoadWorkflowDefinitions::WITH_INIT_OPTION, $crawler->html());
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

    private function assertTransitionFromSubmit(Crawler $crawler, WorkflowItem $workflowItem)
    {
        $form = $crawler->selectButton('Submit')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString((string)$workflowItem->getId(), $crawler->html());
        self::assertStringContainsString($workflowItem->getWorkflowName(), $crawler->html());
        self::assertStringContainsString((string)$workflowItem->getEntityId() . '', $crawler->html());

        $workflowItemNew = $this->getWorkflowItem($this->entity, LoadWorkflowDefinitions::MULTISTEP);

        $this->assertNotEquals(
            $workflowItem->getCurrentStep()->getName(),
            $workflowItemNew->getCurrentStep()->getName()
        );
        $this->assertEquals('second_point', $workflowItemNew->getCurrentStep()->getName());
    }

    private function assertTransitionFromWithConfigurationSubmit(
        Crawler $crawler,
        WorkflowItem $workflowItem,
        string $dataAttribute,
        array $data = []
    ): void {
        $form = $crawler->selectButton('Save')->form($data);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        if ($workflowItem->getId()) {
            self::assertStringContainsString(sprintf('"id":%d', $workflowItem->getId()), $crawler->html());
        }
        self::assertStringContainsString(
            sprintf('"workflow_name":"%s"', $workflowItem->getWorkflowName()),
            $crawler->html()
        );
        self::assertStringContainsString(sprintf('"entity_id":"%s"', $workflowItem->getEntityId()), $crawler->html());

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
                'entityClass' => WorkflowAwareEntity::class,
                'entityId' => $this->entity->getId(),
            ])
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $transitionButton = $crawler->selectLink('oro.workflow.test_active_flow1.transition.transition1.button_label');
        $this->assertCount(1, $transitionButton);
        $this->assertSame('#', $transitionButton->attr('href'));
        self::assertStringContainsString(
            'transition-test_multistep_flow-starting_point_transition',
            $crawler->html()
        );
        self::assertStringContainsString('transition-test_start_step_flow-start_transition', $crawler->html());
        self::assertStringContainsString('transition-test_start_init_option-start_transition', $crawler->html());
        self::assertStringContainsString($this->getTransitionLabel(
            LoadWorkflowDefinitionsWithFormConfiguration::WFC_WORKFLOW_NAME,
            LoadWorkflowDefinitionsWithFormConfiguration::WFC_START_TRANSITION
        ), $crawler->html());
        self::assertStringNotContainsString(
            'transition-test_start_init_option-start_transition_from_entities',
            $crawler->html()
        );
    }

    private function createNewEntity(): WorkflowAwareEntity
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid('test', true));

        $em = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $em->persist($testEntity);
        $em->flush($testEntity);

        return $testEntity;
    }

    private function getWorkflowItem(WorkflowAwareEntity $entity, string $workflowName): WorkflowItem
    {
        return $this->getContainer()
            ->get('oro_workflow.manager')
            ->getWorkflowItem($entity, $workflowName);
    }
}
