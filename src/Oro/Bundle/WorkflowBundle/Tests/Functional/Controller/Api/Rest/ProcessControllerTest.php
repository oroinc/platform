<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessControllerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->entityManager = $this->client->getContainer()->get('doctrine')
            ->getManagerForClass('OroWorkflowBundle:ProcessDefinition');
    }

    public function testActivateDeactivate()
    {
        $definition = $this->createNewEnabledProcessDefinition();
        $definitionName = $definition->getName();
        $this->assertTrue($definition->isEnabled());

        // deactivate process
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_process_deactivate',
                ['processDefinition' => $definitionName]
            )
        );

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));

        // assert that process definition item was deactivated
        $definition = $this->refreshEntity($definition);
        $this->assertFalse($definition->isEnabled());

        // activate process
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_process_activate',
                ['processDefinition' => $definitionName]
            )
        );

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));

        // assert that process definition item was activated
        $definition = $this->refreshEntity($definition);
        $this->assertTrue($definition->isEnabled());
    }

    /**
     * Refresh entity
     *
     * @param ProcessDefinition $definition
     *
     * @return ProcessDefinition
     */
    protected function refreshEntity(ProcessDefinition $definition)
    {
        $definition = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository('OroWorkflowBundle:ProcessDefinition')
            ->findOneBy(['name' => $definition->getName()]);

        return $definition;
    }

    /**
     * @param array $result
     */
    protected function assertResult($result)
    {
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['successful']);
        $this->assertNotEmpty($result['message']);
    }

    protected function createNewEnabledProcessDefinition()
    {
        $testEntity = new ProcessDefinition();
        $testEntity
            ->setName('test_' . uniqid())
            ->setLabel('Test ' . uniqid())
            ->setEnabled(true)
            ->setRelatedEntity('My/Test/Entity');

        $this->entityManager->persist($testEntity);
        $this->entityManager->flush($testEntity);

        return $testEntity;
    }
}
