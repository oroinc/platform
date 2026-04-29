<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadWebhookProducerSettingsData;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WebhookTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebhookAccessibleEntities();
        $this->loadFixtures([LoadWebhookProducerSettingsData::class]);
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

    private function getWebhook(string $webhookReference): WebhookProducerSettings
    {
        return $this->getReference($webhookReference);
    }

    private function findWebhook(string $webhookId): WebhookProducerSettings
    {
        return $this->getEntityManager(WebhookProducerSettings::class)->find(
            WebhookProducerSettings::class,
            $webhookId
        );
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'webhooks']);

        $this->assertResponseContains('cget_webhook.yml', $response, true);

        // verify that secret field is not exposed
        $content = self::jsonToArray($response->getContent());
        foreach ($content['data'] as $item) {
            self::assertArrayNotHasKey('secret', $item['attributes']);
        }
    }

    public function testGet(): void
    {
        $response = $this->get([
            'entity' => 'webhooks',
            'id' => $this->getWebhook('oro_integration:webhook_department.create_enabled')->getId()
        ]);

        $this->assertResponseContains('get_webhook.yml', $response);

        // verify that secret field is not exposed
        $content = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('secret', $content['data']['attributes']);
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'webhooks',
                'attributes' => [
                    'notificationUrl' => 'https://example.com/webhook/new',
                    'secret' => 'test_secret_value',
                    'enabled' => true
                ],
                'relationships' => [
                    'topic' => [
                        'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.created']
                    ],
                    'format' => [
                        'data' => ['type' => 'webhookformats', 'id' => 'default']
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'webhooks'],
            $data
        );

        $expectedData = $data;
        unset($expectedData['data']['attributes']['secret']);
        $expectedData['data']['attributes']['system'] = false;
        $this->assertResponseContains($expectedData, $response);

        // verify secret is not exposed in create response
        $content = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('secret', $content['data']['attributes']);

        // verify the webhook was created with correct data
        $webhook = $this->findWebhook($this->getResourceId($response));
        self::assertEquals('https://example.com/webhook/new', $webhook->getNotificationUrl());
        self::assertEquals('testdepartment.created', $webhook->getTopic());
        self::assertEquals('test_secret_value', $webhook->getSecret());
        self::assertTrue($webhook->isEnabled());
        self::assertFalse($webhook->isSystem());
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $ownerId = $this->getReference(LoadUser::USER)->getId();
        $organizationId = $this->getReference(LoadOrganization::ORGANIZATION)->getId();

        $response = $this->post(
            ['entity' => 'webhooks'],
            [
                'data' => [
                    'type' => 'webhooks',
                    'attributes' => [
                        'notificationUrl' => 'https://example.com/webhook/new'
                    ],
                    'relationships' => [
                        'topic' => [
                            'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.created']
                        ],
                        'format' => [
                            'data' => ['type' => 'webhookformats', 'id' => 'thin']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'webhooks',
                    'attributes' => [
                        'notificationUrl' => 'https://example.com/webhook/new',
                        'enabled' => true,
                        'system' => false
                    ],
                    'relationships' => [
                        'topic' => [
                            'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.created']
                        ],
                        'format' => [
                            'data' => ['type' => 'webhookformats', 'id' => 'thin']
                        ],
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => (string)$ownerId]
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => (string)$organizationId]
                        ]
                    ]
                ]
            ],
            $response
        );

        // verify secret is not exposed in create response
        $content = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('secret', $content['data']['attributes']);

        // verify the webhook was created with correct data
        $webhook = $this->findWebhook($this->getResourceId($response));
        self::assertEquals('https://example.com/webhook/new', $webhook->getNotificationUrl());
        self::assertEquals('testdepartment.created', $webhook->getTopic());
        self::assertEquals('thin', $webhook->getFormat());
        self::assertSame('', $webhook->getSecret());
        self::assertTrue($webhook->isEnabled());
        self::assertFalse($webhook->isSystem());
        self::assertEquals($ownerId, $webhook->getOwner()->getId());
        self::assertEquals($organizationId, $webhook->getOrganization()->getId());
    }

    public function testCreateWithoutSecret(): void
    {
        $response = $this->post(
            ['entity' => 'webhooks'],
            [
                'data' => [
                    'type' => 'webhooks',
                    'attributes' => [
                        'notificationUrl' => 'https://example.com/webhook/no-secret',
                        'enabled' => false
                    ],
                    'relationships' => [
                        'topic' => [
                            'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.updated']
                        ],
                        'format' => [
                            'data' => ['type' => 'webhookformats', 'id' => 'default']
                        ]
                    ]
                ]
            ]
        );

        $webhook = $this->findWebhook($this->getResourceId($response));
        self::assertEmpty($webhook->getSecret());
    }

    public function testUpdate(): void
    {
        $webhookId = $this->getWebhook('oro_integration:webhook_department.create_enabled')->getId();

        $response = $this->patch(
            ['entity' => 'webhooks', 'id' => $webhookId],
            [
                'data' => [
                    'type' => 'webhooks',
                    'id' => $webhookId,
                    'attributes' => [
                        'enabled' => false,
                        'notificationUrl' => 'https://example.com/webhook/updated'
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'webhooks',
                    'id' => $webhookId,
                    'attributes' => [
                        'notificationUrl' => 'https://example.com/webhook/updated',
                        'enabled' => false,
                        'system' => false
                    ]
                ]
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $webhookId = $this->getWebhook('oro_integration:webhook_employee.delete_disabled')->getId();

        $this->delete(['entity' => 'webhooks', 'id' => $webhookId]);

        $deletedWebhook = $this->getEntityManager(WebhookProducerSettings::class)
            ->find(WebhookProducerSettings::class, $webhookId);
        self::assertNull($deletedWebhook);
    }

    public function testGetListFilterByTopic(): void
    {
        $response = $this->cget(
            ['entity' => 'webhooks'],
            ['filter[topic]' => 'testdepartment.created']
        );

        $content = self::jsonToArray($response->getContent());
        self::assertCount(4, $content['data']);
        foreach ($content['data'] as $item) {
            self::assertEquals('testdepartment.created', $item['relationships']['topic']['data']['id']);
        }
    }

    public function testGetListFilterByMultipleTopics(): void
    {
        $response = $this->cget(
            ['entity' => 'webhooks'],
            ['filter[topic]' => 'testdepartment.created,testemployee.created']
        );

        $content = self::jsonToArray($response->getContent());
        self::assertCount(5, $content['data']);
        foreach ($content['data'] as $item) {
            self::assertContains(
                $item['relationships']['topic']['data']['id'],
                ['testdepartment.created', 'testemployee.created']
            );
        }
    }

    public function testGetListWhenFormatRelationshipShouldBeExpanded(): void
    {
        $response = $this->cget(
            ['entity' => 'webhooks'],
            [
                'filter[topic]' => 'testdepartment.updated,testemployee.deleted',
                'include' => 'format'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'webhooks',
                        'id' => '<toString(@oro_integration:webhook_employee.delete_disabled->id)>',
                        'attributes' => [
                            'notificationUrl' => 'https://example.com/webhook/employee.deleted'
                        ],
                        'relationships' => [
                            'format' => [
                                'data' => ['type' => 'webhookformats', 'id' => 'default']
                            ]
                        ]
                    ],
                    [
                        'type' => 'webhooks',
                        'id' => '<toString(@oro_integration:webhook_department.update_enabled->id)>',
                        'attributes' => [
                            'notificationUrl' => 'https://example.com/webhook/department.updated'
                        ],
                        'relationships' => [
                            'format' => [
                                'data' => ['type' => 'webhookformats', 'id' => 'default']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'webhookformats',
                        'id' => 'default',
                        'attributes' => [
                            'label' => 'Default (JSON:API)'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testTryToCreateWithoutNotificationUrl(): void
    {
        $response = $this->post(
            ['entity' => 'webhooks'],
            [
                'data' => [
                    'type' => 'webhooks',
                    'relationships' => [
                        'topic' => [
                            'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.created']
                        ],
                        'format' => [
                            'data' => ['type' => 'webhookformats', 'id' => 'default']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/notificationUrl']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutTopic(): void
    {
        $response = $this->post(
            ['entity' => 'webhooks'],
            [
                'data' => [
                    'type' => 'webhooks',
                    'attributes' => [
                        'notificationUrl' => 'https://example.com/webhook/test'
                    ],
                    'relationships' => [
                        'format' => [
                            'data' => ['type' => 'webhookformats', 'id' => 'default']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/topic/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidTopic(): void
    {
        $response = $this->post(
            ['entity' => 'webhooks'],
            [
                'data' => [
                    'type' => 'webhooks',
                    'attributes' => [
                        'notificationUrl' => 'https://example.com/webhook/new'
                    ],
                    'relationships' => [
                        'topic' => [
                            'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.other']
                        ],
                        'format' => [
                            'data' => ['type' => 'webhookformats', 'id' => 'default']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'valid webhook topic constraint',
                'detail' => 'This value is not a known webhook topic.',
                'source' => ['pointer' => '/data/relationships/topic/data']
            ],
            $response
        );
    }

    public function testTryToSetInvalidTopicOnUpdate(): void
    {
        $webhookId = $this->getWebhook('oro_integration:webhook_department.create_enabled')->getId();

        $response = $this->patch(
            ['entity' => 'webhooks', 'id' => $webhookId],
            [
                'data' => [
                    'type' => 'webhooks',
                    'id' => $webhookId,
                    'relationships' => [
                        'topic' => [
                            'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.other']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'valid webhook topic constraint',
                'detail' => 'This value is not a known webhook topic.',
                'source' => ['pointer' => '/data/relationships/topic/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutFormat(): void
    {
        $response = $this->post(
            ['entity' => 'webhooks'],
            [
                'data' => [
                    'type' => 'webhooks',
                    'attributes' => [
                        'notificationUrl' => 'https://example.com/webhook/test'
                    ],
                    'relationships' => [
                        'topic' => [
                            'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.created']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/format/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidFormat(): void
    {
        $response = $this->post(
            ['entity' => 'webhooks'],
            [
                'data' => [
                    'type' => 'webhooks',
                    'attributes' => [
                        'notificationUrl' => 'https://example.com/webhook/new'
                    ],
                    'relationships' => [
                        'topic' => [
                            'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.created']
                        ],
                        'format' => [
                            'data' => ['type' => 'webhookformats', 'id' => 'other']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'valid webhook format constraint',
                'detail' => 'This value is not a known webhook format.',
                'source' => ['pointer' => '/data/relationships/format/data']
            ],
            $response
        );
    }

    public function testTryToSetInvalidFormatOnUpdate(): void
    {
        $webhookId = $this->getWebhook('oro_integration:webhook_department.create_enabled')->getId();

        $response = $this->patch(
            ['entity' => 'webhooks', 'id' => $webhookId],
            [
                'data' => [
                    'type' => 'webhooks',
                    'id' => $webhookId,
                    'relationships' => [
                        'format' => [
                            'data' => ['type' => 'webhookformats', 'id' => 'other']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'valid webhook format constraint',
                'detail' => 'This value is not a known webhook format.',
                'source' => ['pointer' => '/data/relationships/format/data']
            ],
            $response
        );
    }

    public function testGetNonExistentWebhook(): void
    {
        $response = $this->get(
            ['entity' => 'webhooks', 'id' => '00000000-0000-0000-0000-000000000000'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetWithInvalidId(): void
    {
        $response = $this->get(
            ['entity' => 'webhooks', 'id' => 'invalid-id'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            ['title' => 'entity identifier constraint'],
            $response
        );
    }

    public function testTryToUpdateWithInvalidId(): void
    {
        $response = $this->patch(
            ['entity' => 'webhooks', 'id' => 'invalid-id'],
            [
                'data' => [
                    'type' => 'webhooks',
                    'id' => 'invalid-id',
                    'attributes' => [
                        'enabled' => false
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            ['title' => 'entity identifier constraint'],
            $response
        );
    }

    public function testTryToDeleteWithInvalidId(): void
    {
        $response = $this->delete(
            ['entity' => 'webhooks', 'id' => 'invalid-id'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            ['title' => 'entity identifier constraint'],
            $response
        );
    }

    public function testGetSystemWebhook(): void
    {
        $response = $this->get([
            'entity' => 'webhooks',
            'id' => $this->getWebhook('oro_integration:webhook_department.create_system')->getId()
        ]);

        $content = self::jsonToArray($response->getContent());
        self::assertTrue($content['data']['attributes']['system']);
    }

    public function testTryToSetSystemFlagOnCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'webhooks',
                'attributes' => [
                    'notificationUrl' => 'https://example.com/webhook/test-system',
                    'system' => true
                ],
                'relationships' => [
                    'topic' => [
                        'data' => ['type' => 'webhooktopics', 'id' => 'testdepartment.created']
                    ],
                    'format' => [
                        'data' => ['type' => 'webhookformats', 'id' => 'default']
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'webhooks'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['system'] = false;
        $this->assertResponseContains($expectedData, $response);

        $webhook = $this->findWebhook($this->getResourceId($response));
        self::assertFalse($webhook->isSystem());
    }

    public function testTryToSetSystemFlagOnUpdate(): void
    {
        $webhookId = $this->getWebhook('oro_integration:webhook_department.create_enabled')->getId();

        $response = $this->patch(
            ['entity' => 'webhooks', 'id' => $webhookId],
            [
                'data' => [
                    'type' => 'webhooks',
                    'id' => $webhookId,
                    'attributes' => [
                        'system' => true
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'webhooks',
                    'id' => $webhookId,
                    'attributes' => [
                        'system' => false
                    ]
                ]
            ],
            $response
        );

        $webhook = $this->findWebhook($webhookId);
        self::assertFalse($webhook->isSystem());
    }

    public function testGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'webhooks']
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'webhooks', 'id' => '1']
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, DELETE');
    }
}
