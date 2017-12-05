<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionFactory;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionMetadataFactory;

class MassActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var MassActionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $actionFactory;

    /** @var MassActionMetadataFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $actionMetadataFactory;

    /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var CsrfTokenManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
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
    }

    public function testIsApplicable()
    {
        self::assertTrue($this->extension->isApplicable(DatagridConfiguration::create([])));
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

        $this->tokenManager->expects(self::once())
            ->method('getToken')
            ->with($actionName)
            ->willReturn(new CsrfToken($actionName, 'csrf-token'));

        self::assertNull(
            $this->extension->getMassAction($actionName, $datagrid)
        );
    }
}
