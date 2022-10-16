<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocContextProvider;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocFiltersHandler;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocHandler;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocIdentifierHandler;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocStatusCodesHandler;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Controller\RestApiController;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Symfony\Component\Routing\Route;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestDocHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const ROUTE_GROUP = 'test_route_group';

    /** @var RestDocViewDetector|\PHPUnit\Framework\MockObject\MockObject */
    private $docViewDetector;

    /** @var RestDocContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contextProvider;

    /** @var ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueNormalizer;

    /** @var RestDocIdentifierHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $identifierHandler;

    /** @var RestDocFiltersHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $filtersHandler;

    /** @var RestDocHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->docViewDetector = $this->createMock(RestDocViewDetector::class);
        $this->contextProvider = $this->createMock(RestDocContextProvider::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->identifierHandler = $this->createMock(RestDocIdentifierHandler::class);
        $this->filtersHandler = $this->createMock(RestDocFiltersHandler::class);

        $this->handler = new RestDocHandler(
            self::ROUTE_GROUP,
            $this->docViewDetector,
            $this->contextProvider,
            $this->valueNormalizer,
            $this->identifierHandler,
            $this->filtersHandler,
            new RestDocStatusCodesHandler()
        );
    }

    private function doHandle(ApiDoc $annotation, Route $route): void
    {
        $this->handler->handle(
            $annotation,
            [],
            $route,
            new \ReflectionMethod(RestApiController::class, 'itemAction')
        );
    }

    private function convertAnnotationToArray(ApiDoc $annotation): array
    {
        $result = $annotation->toArray();
        unset(
            $result['method'],
            $result['uri'],
            $result['https'],
            $result['authentication'],
            $result['authenticationRoles'],
            $result['deprecated']
        );
        if (null !== $annotation->getInput()) {
            $result['input'] = $annotation->getInput();
        }
        if (null !== $annotation->getOutput()) {
            $result['output'] = $annotation->getOutput();
        }

        return $result;
    }

    public function testHandleForRouteWithoutGroupOption()
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route');

        $this->doHandle($annotation, $route);

        self::assertEquals([], $this->convertAnnotationToArray($annotation));
    }

    public function testHandleForRouteWithUnsupportedGroupOption()
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route', [], [], ['group' => 'another']);

        $this->doHandle($annotation, $route);

        self::assertEquals([], $this->convertAnnotationToArray($annotation));
    }

    public function testHandleForEmptyRequestType()
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route', [], [], ['group' => self::ROUTE_GROUP]);

        $this->docViewDetector->expects(self::once())
            ->method('getRequestType')
            ->willReturn(new RequestType([]));

        $this->doHandle($annotation, $route);

        self::assertEquals([], $this->convertAnnotationToArray($annotation));
    }

    public function testHandleForRouteWithoutActionAttribute()
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route', [], [], ['group' => self::ROUTE_GROUP]);

        $this->docViewDetector->expects(self::once())
            ->method('getRequestType')
            ->willReturn(new RequestType([RequestType::REST]));

        $this->doHandle($annotation, $route);

        self::assertEquals([], $this->convertAnnotationToArray($annotation));
    }

    public function testHandleForRouteWithoutEntityAttribute()
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route', ['_action' => ApiAction::GET], [], ['group' => self::ROUTE_GROUP]);

        $this->docViewDetector->expects(self::once())
            ->method('getRequestType')
            ->willReturn(new RequestType([RequestType::REST]));

        $this->doHandle($annotation, $route);

        self::assertEquals([], $this->convertAnnotationToArray($annotation));
    }

    public function testHandleForResourceWithoutConfig()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::GET;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn(null);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::once())
            ->method('getContext')
            ->with($action, $entityClass, self::isNull(), self::identicalTo($route))
            ->willReturn($context);

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section' => 'test_entity'
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    public function testHandleForResourceWithoutMetadata()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::GET;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route/{id}',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn(new EntityDefinitionConfig());
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn(null);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::once())
            ->method('getContext')
            ->with($action, $entityClass, self::isNull(), self::identicalTo($route))
            ->willReturn($context);

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section' => 'test_entity'
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    public function testHandleForPrimaryResourceWithEmptyConfig()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::GET;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route/{id}',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $config = new EntityDefinitionConfig();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $filters = new FilterCollection();

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $context->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::once())
            ->method('getContext')
            ->with($action, $entityClass, self::isNull(), self::identicalTo($route))
            ->willReturn($context);
        $this->identifierHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($route),
                self::identicalTo($metadata),
                self::isNull()
            );
        $this->filtersHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($filters),
                self::identicalTo($metadata)
            );

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section' => 'test_entity'
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    public function testHandleForPrimaryResourceWithIdentifier()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::GET;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route/{id}',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $config = new EntityDefinitionConfig();
        $config->setDescription('A description');
        $config->setDocumentation('A documentation');
        $config->setIdentifierDescription('An identifier description');
        $statusCodes = new StatusCodesConfig();
        $statusCodes->addCode('200');
        $config->setStatusCodes($statusCodes);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $filters = new FilterCollection();

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $context->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::once())
            ->method('getContext')
            ->with($action, $entityClass, self::isNull(), self::identicalTo($route))
            ->willReturn($context);
        $this->identifierHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($route),
                self::identicalTo($metadata),
                'An identifier description'
            );
        $this->filtersHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($filters),
                self::identicalTo($metadata)
            );

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section'       => 'test_entity',
                'description'   => 'A description',
                'documentation' => 'A documentation',
                'statusCodes'   => [
                    200 => [null]
                ],
                'output'        => [
                    'class'   => '',
                    'options' => [
                        'direction' => 'output',
                        'metadata'  => new ApiDocMetadata($action, $metadata, $config, $requestType)
                    ]
                ]
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    public function testHandleForPrimaryResourceWithoutIdentifier()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::GET_LIST;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $config = new EntityDefinitionConfig();
        $config->setDescription('A description');
        $config->setDocumentation('A documentation');
        $config->setIdentifierDescription('An identifier description');
        $statusCodes = new StatusCodesConfig();
        $statusCodes->addCode('200');
        $config->setStatusCodes($statusCodes);

        $metadata = new EntityMetadata('Test\Entity');

        $filters = new FilterCollection();

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $context->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::once())
            ->method('getContext')
            ->with($action, $entityClass, self::isNull(), self::identicalTo($route))
            ->willReturn($context);
        $this->identifierHandler->expects(self::never())
            ->method('handle');
        $this->filtersHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($filters),
                self::identicalTo($metadata)
            );

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section'       => 'test_entity',
                'description'   => 'A description',
                'documentation' => 'A documentation',
                'statusCodes'   => [
                    200 => [null]
                ],
                'output'        => [
                    'class'   => '',
                    'options' => [
                        'direction' => 'output',
                        'metadata'  => new ApiDocMetadata($action, $metadata, $config, $requestType)
                    ]
                ]
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHandleForPrimaryResourceWithInputData()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::UPDATE;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route/{id}',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $config = new EntityDefinitionConfig();
        $config->setDescription('A description');
        $config->setDocumentation('A documentation');
        $config->setIdentifierDescription('An identifier description');
        $statusCodes = new StatusCodesConfig();
        $statusCodes->addCode('200');
        $config->setStatusCodes($statusCodes);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $filters = new FilterCollection();

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $context->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $getConfig = new EntityDefinitionConfig();
        $getMetadata = new EntityMetadata('Test\Entity');
        $getMetadata->setIdentifierFieldNames(['id']);
        $getContext = $this->createMock(Context::class);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::exactly(2))
            ->method('getContext')
            ->willReturnMap([
                [$action, $entityClass, null, $route, $context],
                [ApiAction::GET, $entityClass, null, null, $getContext]
            ]);
        $this->contextProvider->expects(self::once())
            ->method('getConfig')
            ->with(self::identicalTo($getContext))
            ->willReturn($getConfig);
        $this->contextProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::identicalTo($getContext))
            ->willReturn($getMetadata);
        $this->identifierHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($route),
                self::identicalTo($metadata),
                'An identifier description'
            );
        $this->filtersHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($filters),
                self::identicalTo($metadata)
            );

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section'       => 'test_entity',
                'description'   => 'A description',
                'documentation' => 'A documentation',
                'statusCodes'   => [
                    200 => [null]
                ],
                'input'         => [
                    'class'   => '',
                    'options' => [
                        'direction' => 'input',
                        'metadata'  => new ApiDocMetadata($action, $metadata, $config, $requestType)
                    ]
                ],
                'output'        => [
                    'class'   => '',
                    'options' => [
                        'direction' => 'output',
                        'metadata'  => new ApiDocMetadata($action, $getMetadata, $getConfig, $requestType)
                    ]
                ]
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    public function testHandleForPrimaryResourceWithInputDataAndWithoutOutputData()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::UPDATE;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $config = new EntityDefinitionConfig();
        $config->setDescription('A description');
        $config->setDocumentation('A documentation');
        $config->setIdentifierDescription('An identifier description');
        $statusCodes = new StatusCodesConfig();
        $statusCodes->addCode('204');
        $config->setStatusCodes($statusCodes);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $filters = new FilterCollection();

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $context->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::once())
            ->method('getContext')
            ->with($action, $entityClass, self::isNull(), self::identicalTo($route))
            ->willReturn($context);
        $this->identifierHandler->expects(self::never())
            ->method('handle');
        $this->filtersHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($filters),
                self::identicalTo($metadata)
            );

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section'       => 'test_entity',
                'description'   => 'A description',
                'documentation' => 'A documentation',
                'statusCodes'   => [
                    204 => [null]
                ],
                'input'         => [
                    'class'   => '',
                    'options' => [
                        'direction' => 'input',
                        'metadata'  => new ApiDocMetadata($action, $metadata, $config, $requestType)
                    ]
                ]
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHandleForSubresource()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::GET_SUBRESOURCE;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $association = 'testAssociation';
        $route = new Route(
            '/test_route/{id}/{association}',
            ['_action' => $action, 'entity' => $entityType, 'association' => $association],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $config = new EntityDefinitionConfig();
        $config->setDescription('A description');
        $config->setDocumentation('A documentation');
        $config->setIdentifierDescription('An identifier description');
        $statusCodes = new StatusCodesConfig();
        $statusCodes->addCode('200');
        $config->setStatusCodes($statusCodes);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $filters = new FilterCollection();

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierDescription('A parent identifier description');
        $parentStatusCodes = new StatusCodesConfig();
        $parentStatusCodes->addCode('200');
        $parentConfig->setStatusCodes($parentStatusCodes);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames(['id']);

        $context = $this->createMock(SubresourceContext::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $context->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);
        $context->expects(self::once())
            ->method('getParentConfig')
            ->willReturn($parentConfig);
        $context->expects(self::once())
            ->method('getParentMetadata')
            ->willReturn($parentMetadata);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::once())
            ->method('getContext')
            ->with($action, $entityClass, $association, self::identicalTo($route))
            ->willReturn($context);
        $this->identifierHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($route),
                self::identicalTo($parentMetadata),
                'A parent identifier description'
            );
        $this->filtersHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($filters),
                self::identicalTo($metadata)
            );

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section'       => 'test_entity',
                'description'   => 'A description',
                'documentation' => 'A documentation',
                'statusCodes'   => [
                    200 => [null]
                ],
                'output'        => [
                    'class'   => '',
                    'options' => [
                        'direction' => 'output',
                        'metadata'  => new ApiDocMetadata($action, $metadata, $config, $requestType)
                    ]
                ]
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHandleForUpdateListAction()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::UPDATE_LIST;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $config = new EntityDefinitionConfig();
        $config->setDescription('A description');
        $config->setDocumentation('A documentation');
        $config->setIdentifierDescription('An identifier description');
        $statusCodes = new StatusCodesConfig();
        $statusCodes->addCode('200');
        $config->setStatusCodes($statusCodes);

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $asyncOperationConfig = new EntityDefinitionConfig();
        $asyncOperationMetadata = new EntityMetadata('Test\Entity');
        $asyncOperationContext = $this->createMock(Context::class);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::exactly(2))
            ->method('getContext')
            ->willReturnMap([
                [$action, $entityClass, null, $route, $context],
                [$action, AsyncOperation::class, null, $route, $asyncOperationContext]
            ]);
        $this->contextProvider->expects(self::once())
            ->method('getConfig')
            ->with(self::identicalTo($asyncOperationContext))
            ->willReturn($asyncOperationConfig);
        $this->contextProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::identicalTo($asyncOperationContext))
            ->willReturn($asyncOperationMetadata);
        $this->identifierHandler->expects(self::never())
            ->method('handle');
        $this->filtersHandler->expects(self::never())
            ->method('handle');

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section'       => 'test_entity',
                'description'   => 'A description',
                'documentation' => 'A documentation',
                'statusCodes'   => [
                    200 => [null]
                ],
                'output'        => [
                    'class'   => '',
                    'options' => [
                        'direction' => 'output',
                        'metadata'  => new ApiDocMetadata(
                            $action,
                            $asyncOperationMetadata,
                            $asyncOperationConfig,
                            $requestType
                        )
                    ]
                ]
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }

    public function testHandleForOptionsAction()
    {
        $annotation = new ApiDoc([]);
        $requestType = new RequestType([RequestType::REST]);
        $action = ApiAction::OPTIONS;
        $entityType = 'test_entity';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route',
            ['_action' => $action, 'entity' => $entityType],
            [],
            ['group' => self::ROUTE_GROUP]
        );

        $config = new EntityDefinitionConfig();
        $config->setDescription('A description');
        $config->setDocumentation('A documentation');
        $config->setIdentifierDescription('An identifier description');
        $statusCodes = new StatusCodesConfig();
        $statusCodes->addCode('200');
        $config->setStatusCodes($statusCodes);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $filters = new FilterCollection();

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $context->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->docViewDetector->expects(self::atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);
        $this->contextProvider->expects(self::once())
            ->method('getContext')
            ->with($action, $entityClass, self::isNull(), self::identicalTo($route))
            ->willReturn($context);
        $this->identifierHandler->expects(self::never())
            ->method('handle');
        $this->filtersHandler->expects(self::once())
            ->method('handle')
            ->with(
                self::identicalTo($annotation),
                self::identicalTo($filters),
                self::identicalTo($metadata)
            );

        $this->doHandle($annotation, $route);

        self::assertEquals(
            [
                'section'       => 'test_entity',
                'description'   => 'A description',
                'documentation' => 'A documentation',
                'statusCodes'   => [
                    200 => [null]
                ]
            ],
            $this->convertAnnotationToArray($annotation)
        );
    }
}
