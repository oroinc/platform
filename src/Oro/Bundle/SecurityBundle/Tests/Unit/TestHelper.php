<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Metadata\ActionSecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnershipDecisionMaker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class TestHelper
{
    public static function get(\PHPUnit\Framework\TestCase $testCase)
    {
        return new TestHelper($testCase);
    }

    /** @var \PHPUnit\Framework\TestCase */
    private $testCase;

    public function __construct(\PHPUnit\Framework\TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    public function createAclExtensionSelector(
        OwnershipMetadataProviderInterface $metadataProvider = null,
        OwnerTree $ownerTree = null,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker = null
    ): AclExtensionSelector {
        $doctrineHelper = $this->testCase->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $idAccessor = new ObjectIdAccessor($doctrineHelper);

        $actionMetadataProvider = $this->testCase->getMockBuilder(ActionSecurityMetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $actionMetadataProvider->expects($this->testCase->any())
            ->method('isKnownAction')
            ->willReturn(true);

        $container = TestContainerBuilder::create()
            ->add('action_acl_extension', new ActionAclExtension($actionMetadataProvider))
            ->add(
                'entity_acl_extension',
                $this->createEntityAclExtension($metadataProvider, $ownerTree, $idAccessor, $decisionMaker)
            )
            ->getContainer($this->testCase);

        return new AclExtensionSelector(
            ['action_acl_extension', 'entity_acl_extension'],
            $container,
            $idAccessor
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createEntityAclExtension(
        OwnershipMetadataProviderInterface $metadataProvider = null,
        OwnerTree $ownerTree = null,
        ObjectIdAccessor $idAccessor = null,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker = null,
        EntityOwnerAccessor $entityOwnerAccessor = null,
        PermissionManager $permissionManager = null,
        AclGroupProviderInterface $groupProvider = null
    ): EntityAclExtension {
        if ($idAccessor === null) {
            $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
                ->disableOriginalConstructor()
                ->getMock();

            $idAccessor = new ObjectIdAccessor($doctrineHelper);
        }
        if ($metadataProvider === null) {
            $metadataProvider = new OwnershipMetadataProviderStub($this->testCase);
        }
        if ($entityOwnerAccessor === null) {
            $entityOwnerAccessor = new EntityOwnerAccessor($metadataProvider, (new InflectorFactory())->build());
        }
        if ($ownerTree === null) {
            $ownerTree = new OwnerTree();
        }

        $treeProvider = $this->testCase->getMockBuilder(OwnerTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $treeProvider->expects($this->testCase->any())
            ->method('getTree')
            ->willReturn($ownerTree);

        if (!$decisionMaker) {
            $decisionMaker = new EntityOwnershipDecisionMaker(
                $treeProvider,
                $idAccessor,
                new EntityOwnerAccessor($metadataProvider, (new InflectorFactory())->build()),
                $metadataProvider,
                $this->testCase->getMockBuilder(TokenAccessorInterface::class)->getMock()
            );
        }

        $config = $this->testCase->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->testCase->any())
            ->method('getEntityNamespaces')
            ->willReturn([
                'Test' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity'
            ]);

        $em = $this->testCase->getMockBuilder(EntityManagerInterface::class)->getMock();
        $em->expects($this->testCase->any())
            ->method('getConfiguration')->willReturn($config);

        $doctrine = $this->testCase->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->testCase->any())
            ->method('getManagers')
            ->willReturn(['default' => $em]);
        $doctrine->expects($this->testCase->any())
            ->method('getManagerForClass')
            ->willReturn(new \stdClass());
        $doctrine->expects($this->testCase->any())
            ->method('getManager')
            ->with($this->testCase->equalTo('default'))
            ->willReturn($em);
        $doctrine->expects($this->testCase->any())
            ->method('getAliasNamespace')
            ->willReturnMap([
                ['Test', 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity'],
            ]);

        $entityMetadataProvider = $this->testCase->getMockBuilder(EntitySecurityMetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadataProvider->expects($this->testCase->any())
            ->method('isProtectedEntity')
            ->willReturn(true);
        $fieldAclExtension = $this->testCase->getMockBuilder(FieldAclExtension::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new EntityAclExtension(
            $idAccessor,
            new EntityClassResolver($doctrine),
            $entityMetadataProvider,
            $metadataProvider,
            $entityOwnerAccessor,
            $decisionMaker,
            $permissionManager ?: $this->getPermissionManagerMock($this->testCase),
            $groupProvider ?: $this->getGroupProviderMock($this->testCase),
            $fieldAclExtension
        );
    }

    public function createFieldAclExtension(
        OwnershipMetadataProviderInterface $metadataProvider = null,
        OwnerTree $ownerTree = null,
        ObjectIdAccessor $idAccessor = null,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker = null,
        ConfigManager $configManager = null
    ): FieldAclExtension {
        if ($idAccessor === null) {
            $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
                ->disableOriginalConstructor()
                ->getMock();

            $idAccessor = new ObjectIdAccessor($doctrineHelper);
        }
        if ($metadataProvider === null) {
            $metadataProvider = new OwnershipMetadataProviderStub($this->testCase);
        }
        if ($ownerTree === null) {
            $ownerTree = new OwnerTree();
        }

        $treeProvider = $this->testCase->getMockBuilder(OwnerTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $treeProvider->expects($this->testCase->any())
            ->method('getTree')
            ->willReturn($ownerTree);

        $entityOwnerAccessor = new EntityOwnerAccessor($metadataProvider, (new InflectorFactory())->build());

        if (!$decisionMaker) {
            $decisionMaker = new EntityOwnershipDecisionMaker(
                $treeProvider,
                $idAccessor,
                $entityOwnerAccessor,
                $metadataProvider,
                $this->testCase->getMockBuilder(TokenAccessorInterface::class)->getMock()
            );
        }

        $config = $this->testCase->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->testCase->any())
            ->method('getEntityNamespaces')
            ->willReturn([
                'Test' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity'
            ]);

        $em = $this->testCase->getMockBuilder(EntityManagerInterface::class)->getMock();
        $em->expects($this->testCase->any())
            ->method('getConfiguration')
            ->willReturn($config);

        $doctrine = $this->testCase->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->testCase->any())
            ->method('getManagers')
            ->willReturn(['default' => $em]);
        $doctrine->expects($this->testCase->any())
            ->method('getManagerForClass')
            ->willReturn(new \stdClass());
        $doctrine->expects($this->testCase->any())
            ->method('getManager')
            ->with($this->testCase->equalTo('default'))
            ->willReturn($em);
        $doctrine->expects($this->testCase->any())
            ->method('getAliasNamespace')
            ->willReturnMap([
                ['Test', 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity']
            ]);

        $entityMetadataProvider = $this->testCase->getMockBuilder(EntitySecurityMetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new FieldAclExtension(
            $idAccessor,
            $metadataProvider,
            $decisionMaker,
            $entityOwnerAccessor,
            $configManager,
            $entityMetadataProvider
        );
    }

    /**
     * @param \PHPUnit\Framework\TestCase $testCase
     *
     * @return PermissionManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getPermissionManagerMock(\PHPUnit\Framework\TestCase $testCase)
    {
        $permissionManager = $testCase->getMockBuilder(PermissionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $permissionManager->expects($testCase->any())
            ->method('getPermissionsMap')
            ->willReturn([
                'VIEW'   => 1,
                'CREATE' => 2,
                'EDIT'   => 3,
                'DELETE' => 4,
                'ASSIGN' => 5,
                'PERMIT' => 6
            ]);

        return $permissionManager;
    }

    /**
     * @param \PHPUnit\Framework\TestCase $testCase
     *
     * @return AclGroupProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getGroupProviderMock(\PHPUnit\Framework\TestCase $testCase)
    {
        $aclGroupProvider = $testCase->getMockBuilder(AclGroupProviderInterface::class)->getMock();
        $aclGroupProvider->expects($testCase->any())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        return $aclGroupProvider;
    }
}
