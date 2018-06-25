<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionFactory;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionMetadataFactory;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MassActionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MassActionFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $actionFactory;

    /** @var MassActionMetadataFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $actionMetadataFactory;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var CsrfTokenManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenManager;

    /** @var MassActionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->actionFactory = $this->createMock(MassActionFactory::class);
        $this->actionMetadataFactory = $this->createMock(MassActionMetadataFactory::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->extension = new MassActionExtension(
            $this->actionFactory,
            $this->actionMetadataFactory,
            $this->authorizationChecker,
            $this->tokenManager
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicable()
    {
        self::assertTrue($this->extension->isApplicable(DatagridConfiguration::create([])));
    }

    public function testIsNotApplicableInImportExportMode()
    {
        $params = new ParameterBag();
        $params->set(
            ParameterBag::DATAGRID_MODES_PARAMETER,
            [DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE]
        );
        $config = DatagridConfiguration::create([]);
        $this->extension->setParameters($params);
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testVisitMetadataWithoutMassActions()
    {
        $config = DatagridConfiguration::create([]);
        $metadata = MetadataObject::create([]);

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'massActions' => []
            ],
            $metadata->toArray()
        );
    }

    public function testVisitMetadataWithMassActions()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $expectedActionConfig = ['type' => 'type1', 'token' => 'csrf-token'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn(null);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'massActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $metadata->toArray()
        );
    }

    public function testVisitMetadataWithMassActionsAndValidHTTPMethods()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $expectedActionConfig = ['type' => 'type1', 'token' => 'csrf-token'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(['POST', 'DELETE']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'massActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $metadata->toArray()
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage Action parameter "allowedRequestTypes" contains wrong HTTP method. Given "POST, DELETE, WRONG", allowed: "GET, POST, DELETE, PUT, PATCH".
     */
    // @codingStandardsIgnoreEnd
    public function testVisitMetadataWithMassActionsAndNotValidHTTPMethods()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $expectedActionConfig = ['type' => 'type1', 'token' => 'csrf-token'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(['POST', 'DELETE', 'WRONG']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitMetadata($config, $metadata);
    }

    public function testVisitMetadataWithAclProtectedMassActionAndAccessGranted()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];
        $expectedActionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1', 'token' => 'csrf-token'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(true);
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'massActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $metadata->toArray()
        );
    }

    public function testVisitMetadataWithAclProtectedMassActionAndAccessDenied()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];
        $expectedActionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1', 'token' => 'csrf-token'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(false);
        $this->actionMetadataFactory->expects(self::never())
            ->method('createActionMetadata');
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitMetadata($config, $metadata);

        self::assertEquals(
            [
                'massActions' => []
            ],
            $metadata->toArray()
        );
    }

    public function testVisitResultWithoutMassActions()
    {
        $config = DatagridConfiguration::create([]);
        $result = ResultsObject::create([]);

        $this->tokenManager->expects(self::never())
            ->method('getToken');

        $this->extension->visitResult($config, $result);

        self::assertArrayHasKey('metadata', $result);
        self::assertEquals(
            [
                'massActions' => []
            ],
            $result['metadata']
        );
    }

    public function testVisitResultWithMassActions()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $expectedActionConfig = ['type' => 'type1', 'token' => 'csrf-token'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $result = ResultsObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn(null);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitResult($config, $result);

        self::assertArrayHasKey('metadata', $result);
        self::assertEquals(
            [
                'massActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $result['metadata']
        );
    }

    public function testVisitResultWithAclProtectedMassActionAndAccessGranted()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];
        $expectedActionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1', 'token' => 'csrf-token'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $result = ResultsObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(true);
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitResult($config, $result);

        self::assertArrayHasKey('metadata', $result);
        self::assertEquals(
            [
                'massActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $result['metadata']
        );
    }

    public function testVisitResultWithAclProtectedMassActionAndAccessDenied()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];
        $expectedActionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1', 'token' => 'csrf-token'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $result = ResultsObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(false);
        $this->actionMetadataFactory->expects(self::never())
            ->method('createActionMetadata');
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitResult($config, $result);

        self::assertArrayHasKey('metadata', $result);
        self::assertEquals(
            [
                'massActions' => []
            ],
            $result['metadata']
        );
    }

    public function testVisitResultAfterVisitMetadata()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $expectedActionConfig = ['type' => 'type1', 'token' => 'csrf-token'];
        $actionMetadata = ['type' => 'type1', 'label' => 'label1'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $metadata = MetadataObject::create([]);
        $result = ResultsObject::create([]);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getName')
            ->willReturn($actionName);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn(null);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        $this->extension->visitMetadata($config, $metadata);
        $this->extension->visitResult($config, $result);

        self::assertArrayNotHasKey('metadata', $result);
        self::assertEquals(
            [
                'massActions' => [
                    $actionName => $actionMetadata
                ]
            ],
            $metadata->toArray()
        );
    }

    public function testGetMassActionWhenNoActions()
    {
        $config = DatagridConfiguration::create([]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $acceptor = $this->createMock(Acceptor::class);
        $datagrid->expects(self::once())
            ->method('getAcceptor')
            ->willReturn($acceptor);
        $acceptor->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->tokenManager->expects(self::never())
            ->method('getToken');

        self::assertNull(
            $this->extension->getMassAction('action1', $datagrid)
        );
    }

    public function testGetMassActionForKnownAction()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];
        $expectedActionConfig = ['type' => 'type1', 'token' => 'csrf-token'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $datagrid = $this->createMock(DatagridInterface::class);
        $acceptor = $this->createMock(Acceptor::class);
        $datagrid->expects(self::once())
            ->method('getAcceptor')
            ->willReturn($acceptor);
        $acceptor->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn(null);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        self::assertSame(
            $action,
            $this->extension->getMassAction($actionName, $datagrid)
        );
    }

    public function testGetMassActionForAclProtectedMassActionAndAccessGranted()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];
        $expectedActionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1', 'token' => 'csrf-token'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $datagrid = $this->createMock(DatagridInterface::class);
        $acceptor = $this->createMock(Acceptor::class);
        $datagrid->expects(self::once())
            ->method('getAcceptor')
            ->willReturn($acceptor);
        $acceptor->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(true);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        self::assertSame(
            $action,
            $this->extension->getMassAction($actionName, $datagrid)
        );
    }

    public function testGetMassActionForAclProtectedMassActionAndAccessDenied()
    {
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1'];
        $expectedActionConfig = ['type' => 'type1', 'acl_resource' => 'acl_resource1', 'token' => 'csrf-token'];

        $config = DatagridConfiguration::create(
            [
                'mass_actions' => [
                    $actionName => $actionConfig
                ]
            ]
        );
        $datagrid = $this->createMock(DatagridInterface::class);
        $acceptor = $this->createMock(Acceptor::class);
        $datagrid->expects(self::once())
            ->method('getAcceptor')
            ->willReturn($acceptor);
        $acceptor->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $action = $this->createMock(MassActionInterface::class);
        $action->expects(self::once())
            ->method('getAclResource')
            ->willReturn($actionConfig['acl_resource']);

        $this->actionFactory->expects(self::once())
            ->method('createAction')
            ->with($actionName, $expectedActionConfig)
            ->willReturn($action);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($actionConfig['acl_resource'])
            ->willReturn(false);
        $actionConfig = $this->createMock(ActionConfiguration::class);
        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionConfig);
        $actionConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(MassActionExtension::ALLOWED_REQUEST_TYPES)
            ->willReturn(null);

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        self::assertNull(
            $this->extension->getMassAction($actionName, $datagrid)
        );
    }
}
