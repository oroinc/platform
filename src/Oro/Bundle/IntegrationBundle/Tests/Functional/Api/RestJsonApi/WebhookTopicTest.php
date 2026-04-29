<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\HttpFoundation\Response;

class WebhookTopicTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebhookAccessibleEntities();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->resetWebhookAccessibleEntities();
        parent::tearDown();
    }

    private function configureWebhookAccessibleEntities(): void
    {
        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_entity_config.config_manager');
        foreach ([TestDepartment::class, TestEmployee::class] as $entityClass) {
            $integrationConfig = $configManager->getEntityConfig('integration', $entityClass);
            $integrationConfig->set('webhook_accessible', true);
            $configManager->persist($integrationConfig);
        }
        $configManager->flush();
    }

    private function resetWebhookAccessibleEntities(): void
    {
        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_entity_config.config_manager');
        foreach ([TestDepartment::class, TestEmployee::class] as $entityClass) {
            $integrationConfig = $configManager->getEntityConfig('integration', $entityClass);
            $integrationConfig->set('webhook_accessible', false);
            $configManager->persist($integrationConfig);
        }
        $configManager->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'webhooktopics']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webhooktopics', 'id' => 'testdepartment.created'],
                    ['type' => 'webhooktopics', 'id' => 'testdepartment.updated'],
                    ['type' => 'webhooktopics', 'id' => 'testdepartment.deleted'],
                    ['type' => 'webhooktopics', 'id' => 'testemployee.created'],
                    ['type' => 'webhooktopics', 'id' => 'testemployee.updated'],
                    ['type' => 'webhooktopics', 'id' => 'testemployee.deleted']
                ]
            ],
            $response,
            true
        );

        // verify structure of webhook topic items
        // webhook topics have an ID identifier and a label attribute, but no relationships
        $content = self::jsonToArray($response->getContent());
        foreach ($content['data'] as $item) {
            self::assertArrayHasKey('attributes', $item);
            self::assertArrayHasKey('label', $item['attributes']);
            self::assertArrayNotHasKey('relationships', $item);
        }
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'webhooktopics', 'id' => 'testdepartment.created']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'webhooktopics',
                    'id' => 'testdepartment.created'
                ]
            ],
            $response
        );

        // verify structure of webhook topic items
        // webhook topics have an ID identifier and a label attribute, but no relationships
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('attributes', $content['data']);
        self::assertArrayHasKey('label', $content['data']['attributes']);
        self::assertArrayNotHasKey('relationships', $content['data']);
    }

    public function testTryToGetForUnknownFormat(): void
    {
        $response = $this->get(
            ['entity' => 'webhooktopics', 'id' => 'testdepartment.other'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'webhooktopics'],
            [
                'data' => [
                    'type' => 'webhooktopics',
                    'id' => 'new_topic'
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'webhooktopics', 'id' => 'testdepartment.created'],
            [
                'data' => [
                    'type' => 'webhooktopics',
                    'id' => 'testdepartment.created'
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'webhooktopics', 'id' => 'testdepartment.created'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'webhooktopics'],
            ['filter' => ['id' => 'testdepartment.created']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'webhooktopics']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'webhooktopics', 'id' => 'testdepartment.created']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }
}
