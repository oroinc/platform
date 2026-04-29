<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\IntegrationBundle\Provider\JsonApiFormatWebhookEventDataProvider;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class JsonApiFormatWebhookEventDataProviderTest extends TestCase
{
    private ActionProcessorBagInterface&MockObject $actionProcessorBag;
    private ActionProcessorInterface&MockObject $actionProcessor;
    private GetContext&MockObject $context;
    private ConfigManager&MockObject $entityConfigManager;
    private JsonApiFormatWebhookEventDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionProcessorBag = $this->createMock(ActionProcessorBagInterface::class);
        $this->actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->context = $this->createMock(GetContext::class);
        $this->entityConfigManager = $this->createMock(ConfigManager::class);

        $this->provider = new JsonApiFormatWebhookEventDataProvider(
            $this->actionProcessorBag,
            $this->entityConfigManager
        );
    }

    /**
     * @dataProvider successfulEventDataProvider
     */
    public function testGetEventDataReturnsProcessedResult(int|string $entityId, array $expectedData): void
    {
        $entityClass = 'Test\Entity';

        $this->setupProcessorMocks($entityClass, $entityId);

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $this->context->expects(self::once())
            ->method('getResponseStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->context->expects(self::once())
            ->method('getResult')
            ->willReturn($expectedData);

        $result = $this->provider->getEventData($entityClass, $entityId);

        self::assertEquals($expectedData, $result);
    }

    public function successfulEventDataProvider(): array
    {
        return [
            'integer entity id' => [
                123,
                [
                    'data' => [
                        'type' => 'test',
                        'id' => 123,
                        'attributes' => ['name' => 'Test Entity', 'status' => 'active']
                    ]
                ]
            ],
            'string entity id' => [
                'uuid-123',
                [
                    'data' => [
                        'type' => 'test',
                        'id' => 'uuid-123',
                        'attributes' => ['name' => 'Test Entity']
                    ]
                ]
            ],
            'result with included relations' => [
                456,
                [
                    'data' => [
                        'type' => 'test',
                        'id' => 456,
                        'attributes' => ['name' => 'Entity With Relations'],
                        'relationships' => [
                            'owner' => ['data' => ['type' => 'users', 'id' => '1']]
                        ]
                    ],
                    'included' => [
                        ['type' => 'users', 'id' => '1', 'attributes' => ['username' => 'admin']]
                    ]
                ]
            ],
        ];
    }

    public function testGetEventDataSetsIncludeFilterWhenConfigured(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $include = 'owner,category';

        $this->setupProcessorMocks($entityClass, $entityId);

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $entityConfig = new Config(
            new EntityConfigId(WebhookConfigurationProvider::ENTITY_CONFIG_SCOPE, $entityClass),
            ['webhook_relations_includes' => $include]
        );

        $this->entityConfigManager->expects(self::once())
            ->method('getEntityConfig')
            ->with(WebhookConfigurationProvider::ENTITY_CONFIG_SCOPE, $entityClass)
            ->willReturn($entityConfig);

        $filterValues = $this->createMock(FilterValueAccessorInterface::class);
        $this->context->expects(self::once())
            ->method('getFilterValues')
            ->willReturn($filterValues);

        $filterValues->expects(self::once())
            ->method('set')
            ->with(
                'include',
                self::callback(static function (FilterValue $fv) use ($include) {
                    return $fv->getPath() === 'include' && $fv->getValue() === $include;
                })
            );

        $this->context->expects(self::once())
            ->method('getResponseStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->context->expects(self::once())
            ->method('getResult')
            ->willReturn(['data' => ['type' => 'test', 'id' => 123]]);

        $this->provider->getEventData($entityClass, $entityId);
    }

    public function testGetEventDataDoesNotSetIncludeFilterWhenNotConfigured(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;

        $this->setupProcessorMocks($entityClass, $entityId);

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $entityConfig = new Config(
            new EntityConfigId(WebhookConfigurationProvider::ENTITY_CONFIG_SCOPE, $entityClass),
            ['webhook_relations_includes' => null]
        );

        $this->entityConfigManager->expects(self::once())
            ->method('getEntityConfig')
            ->with(WebhookConfigurationProvider::ENTITY_CONFIG_SCOPE, $entityClass)
            ->willReturn($entityConfig);

        $this->context->expects(self::never())
            ->method('getFilterValues');

        $this->context->expects(self::once())
            ->method('getResponseStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->context->expects(self::once())
            ->method('getResult')
            ->willReturn(['data' => ['type' => 'test', 'id' => 123]]);

        $this->provider->getEventData($entityClass, $entityId);
    }

    public function testGetEventDataDoesNotSetIncludeFilterWhenEntityHasNoConfig(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;

        $this->setupProcessorMocks($entityClass, $entityId);

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $this->entityConfigManager->expects(self::never())
            ->method('getEntityConfig');

        $this->context->expects(self::never())
            ->method('getFilterValues');

        $this->context->expects(self::once())
            ->method('getResponseStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->context->expects(self::once())
            ->method('getResult')
            ->willReturn(['data' => ['type' => 'test', 'id' => 123]]);

        $this->provider->getEventData($entityClass, $entityId);
    }

    public function testGetEventDataReturnsFallbackOnErrorResponse(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;

        $this->setupProcessorMocks($entityClass, $entityId);

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->willReturn(false);

        $this->context->expects(self::once())
            ->method('getResponseStatusCode')
            ->willReturn(Response::HTTP_NOT_FOUND);

        $this->context->expects(self::any())
            ->method('getClassName')
            ->willReturn($entityClass);

        $this->context->expects(self::any())
            ->method('getId')
            ->willReturn($entityId);

        $this->context->expects(self::once())
            ->method('getResult')
            ->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to serialize entity for webhook',
                [
                    'entity_class' => $entityClass,
                    'entity_id' => $entityId
                ]
            );
        $this->provider->setLogger($logger);

        $result = $this->provider->getEventData($entityClass, $entityId);

        self::assertEquals([
            'data' => [
                'type' => $entityClass,
                'id' => $entityId
            ]
        ], $result);
    }

    public function testGetEventDataHandlesExceptionAndLogsError(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $exception = new \RuntimeException('Test exception');

        $logger = $this->createMock(LoggerInterface::class);
        $this->provider->setLogger($logger);

        $this->actionProcessorBag->expects(self::once())
            ->method('getProcessor')
            ->willThrowException($exception);

        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to serialize entity for webhook',
                [
                    'exception' => $exception,
                    'entity_class' => $entityClass,
                    'entity_id' => $entityId
                ]
            );

        $result = $this->provider->getEventData($entityClass, $entityId);

        self::assertEquals([
            'data' => [
                'type' => $entityClass,
                'id' => $entityId
            ]
        ], $result);
    }

    public function testGetEventDataHandlesExceptionWithoutLogger(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;

        $this->actionProcessorBag->expects(self::once())
            ->method('getProcessor')
            ->willThrowException(new \RuntimeException('Test exception'));

        $result = $this->provider->getEventData($entityClass, $entityId);

        self::assertEquals([
            'data' => [
                'type' => $entityClass,
                'id' => $entityId
            ]
        ], $result);
    }

    public function testGetEventDataReturnsEmptyArrayWhenResultIsNull(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;

        $this->setupProcessorMocks($entityClass, $entityId);

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->willReturn(false);

        $this->context->expects(self::any())
            ->method('getResponseStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->context->expects(self::any())
            ->method('getClassName')
            ->willReturn($entityClass);

        $this->context->expects(self::any())
            ->method('getId')
            ->willReturn($entityId);

        $this->context->expects(self::once())
            ->method('getResult')
            ->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to serialize entity for webhook',
                [
                    'entity_class' => $entityClass,
                    'entity_id' => $entityId
                ]
            );
        $this->provider->setLogger($logger);

        $result = $this->provider->getEventData($entityClass, $entityId);

        self::assertEquals(['data' => ['type' => $entityClass, 'id' => $entityId]], $result);
    }

    private function setupProcessorMocks(string $entityClass, int|string $entityId): void
    {
        $this->actionProcessorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::GET)
            ->willReturn($this->actionProcessor);

        $this->actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($this->context);

        $requestType = $this->createMock(RequestType::class);
        $this->context->expects(self::any())
            ->method('getRequestType')
            ->willReturn($requestType);

        $requestType->expects(self::exactly(2))
            ->method('add')
            ->withConsecutive(
                [RequestType::JSON_API],
                [RequestType::REST]
            );

        $this->context->expects(self::once())
            ->method('setMainRequest')
            ->with(true);

        $this->context->expects(self::once())
            ->method('setClassName')
            ->with($entityClass);

        $this->context->expects(self::once())
            ->method('setId')
            ->with($entityId);

        $this->actionProcessor->expects(self::once())
            ->method('process')
            ->with($this->context);
    }
}
