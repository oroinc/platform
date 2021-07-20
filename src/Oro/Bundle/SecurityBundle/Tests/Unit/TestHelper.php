<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
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
use Symfony\Bridge\Doctrine\ManagerRegistry;

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

    /**
     * @param OwnershipMetadataProviderInterface $metadataProvider
     * @param OwnerTree $ownerTree
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @return AclExtensionSelector
     */
    public function createAclExtensionSelector(
        OwnershipMetadataProviderInterface $metadataProvider = null,
        OwnerTree $ownerTree = null,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker = null
    ) {
        $doctrineHelper = $this->testCase->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $idAccessor = new ObjectIdAccessor($doctrineHelper);

        $actionMetadataProvider = $this->testCase->getMockBuilder(ActionSecurityMetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $actionMetadataProvider->expects($this->testCase->any())
            ->method('isKnownAction')
            ->will($this->testCase->returnValue(true));

        return new AclExtensionSelector(
            [
                new ActionAclExtension($actionMetadataProvider),
                $this->createEntityAclExtension($metadataProvider, $ownerTree, $idAccessor, $decisionMaker)
            ],
            $idAccessor
        );
    }

    /**
     * @param OwnershipMetadataProviderInterface $metadataProvider
     * @param OwnerTree $ownerTree
     * @param ObjectIdAccessor $idAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param EntityOwnerAccessor $entityOwnerAccessor
     * @param PermissionManager $permissionManager
     * @param AclGroupProviderInterface $groupProvider
     * @return EntityAclExtension
     *
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
    ) {
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

        $treeProviderMock = $this->testCase->getMockBuilder(OwnerTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $treeProviderMock->expects($this->testCase->any())
            ->method('getTree')
            ->will($this->testCase->returnValue($ownerTree));

        if (!$decisionMaker) {
            $decisionMaker = new EntityOwnershipDecisionMaker(
                $treeProviderMock,
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
            ->will($this->testCase->returnValue([
                'Test' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity'
            ]));

        $em = $this->testCase->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->testCase->any())
            ->method('getConfiguration')->will($this->testCase->returnValue($config));

        $doctrine = $this->testCase->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->testCase->any())
            ->method('getManagers')
            ->will($this->testCase->returnValue(['default' => $em]));
        $doctrine->expects($this->testCase->any())
            ->method('getManagerForClass')
            ->will($this->testCase->returnValue(new \stdClass()));
        $doctrine->expects($this->testCase->any())
            ->method('getManager')
            ->with($this->testCase->equalTo('default'))
            ->will($this->testCase->returnValue($em));
        $doctrine->expects($this->testCase->any())
            ->method('getAliasNamespace')
            ->will($this->testCase->returnValueMap([
                ['Test', 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity'],
            ]));

        $entityMetadataProvider = $this->testCase->getMockBuilder(EntitySecurityMetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadataProvider->expects($this->testCase->any())
            ->method('isProtectedEntity')
            ->will($this->testCase->returnValue(true));
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

    /**
     * @param OwnershipMetadataProviderInterface $metadataProvider
     * @param OwnerTree $ownerTree
     * @param ObjectIdAccessor $idAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param ConfigManager $configManager
     * @return FieldAclExtension
     */
    public function createFieldAclExtension(
        OwnershipMetadataProviderInterface $metadataProvider = null,
        OwnerTree $ownerTree = null,
        ObjectIdAccessor $idAccessor = null,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker = null,
        ConfigManager $configManager = null
    ) {
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

        $treeProviderMock = $this->testCase->getMockBuilder(OwnerTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $treeProviderMock->expects($this->testCase->any())
            ->method('getTree')
            ->will($this->testCase->returnValue($ownerTree));

        $entityOwnerAccessor = new EntityOwnerAccessor($metadataProvider, (new InflectorFactory())->build());

        if (!$decisionMaker) {
            $decisionMaker = new EntityOwnershipDecisionMaker(
                $treeProviderMock,
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
            ->will($this->testCase->returnValue([
                'Test' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity'
            ]));

        $em = $this->testCase->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->testCase->any())
            ->method('getConfiguration')
            ->will($this->testCase->returnValue($config));

        $doctrine = $this->testCase->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->testCase->any())
            ->method('getManagers')
            ->will($this->testCase->returnValue(['default' => $em]));
        $doctrine->expects($this->testCase->any())
            ->method('getManagerForClass')
            ->will($this->testCase->returnValue(new \stdClass()));
        $doctrine->expects($this->testCase->any())
            ->method('getManager')
            ->with($this->testCase->equalTo('default'))
            ->will($this->testCase->returnValue($em));
        $doctrine->expects($this->testCase->any())
            ->method('getAliasNamespace')
            ->will($this->testCase->returnValueMap([
                ['Test', 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity']
            ]));

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
        $mock = $testCase->getMockBuilder(AclGroupProviderInterface::class)->getMock();
        $mock->expects($testCase->any())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        return $mock;
    }
}
