<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testActivateDeactivate()
    {
        $definition = $this->createNewEnabledProcessDefinition();
        $definitionName = $definition->getName();
        $this->assertTrue($definition->isEnabled());

        // deactivate process
        $this->client->jsonRequest(
            'POST',
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
        $this->client->jsonRequest(
            'POST',
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

    private function refreshEntity(ProcessDefinition $definition): ProcessDefinition
    {
        return self::getContainer()->get('doctrine')->getRepository(ProcessDefinition::class)
            ->findOneBy(['name' => $definition->getName()]);
    }

    private function assertResult(array $result): void
    {
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['successful']);
        $this->assertNotEmpty($result['message']);
    }

    private function createNewEnabledProcessDefinition(): ProcessDefinition
    {
        $testEntity = new ProcessDefinition();
        $testEntity
            ->setName('test_name')
            ->setLabel('Test Label')
            ->setEnabled(true)
            ->setRelatedEntity('My/Test/Entity');

        $em = $this->client->getContainer()->get('doctrine')->getManagerForClass(ProcessDefinition::class);
        $em->persist($testEntity);
        $em->flush($testEntity);

        return $testEntity;
    }
}
