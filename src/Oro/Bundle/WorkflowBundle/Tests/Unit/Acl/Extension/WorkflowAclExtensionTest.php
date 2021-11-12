<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\RootOwnershipMetadata;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension\Stub\DomainObjectStub;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclExtension;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclMetadata;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclMetadataProvider;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowMaskBuilder;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionAclExtension;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowAclExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const PATTERN_ALL_OFF = '(PV) system:.. global:.. deep:.. local:.. basic:..';

    /** @var ObjectIdAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $objectIdAccessor;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataProvider;

    /** @var EntityOwnerAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOwnerAccessor;

    /** @var AccessLevelOwnershipDecisionMakerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $decisionMaker;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var WorkflowAclMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowMetadataProvider;

    /** @var WorkflowTransitionAclExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $transitionAclExtension;

    /** @var WorkflowAclExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->objectIdAccessor = $this->createMock(ObjectIdAccessor::class);
        $this->metadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);
        $this->decisionMaker = $this->createMock(AccessLevelOwnershipDecisionMakerInterface::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->workflowMetadataProvider = $this->createMock(WorkflowAclMetadataProvider::class);
        $this->transitionAclExtension = $this->createMock(WorkflowTransitionAclExtension::class);

        $this->extension = new WorkflowAclExtension(
            $this->objectIdAccessor,
            $this->metadataProvider,
            $this->entityOwnerAccessor,
            $this->decisionMaker,
            $this->workflowManager,
            $this->workflowMetadataProvider,
            $this->transitionAclExtension
        );
    }

    public function testGetExtensionKey()
    {
        self::assertEquals(
            'workflow',
            $this->extension->getExtensionKey()
        );
    }

    public function testSupportsForSupportedId()
    {
        self::assertTrue(
            $this->extension->supports('test', 'workflow')
        );
    }

    public function testSupportsForNotSupportedId()
    {
        self::assertFalse(
            $this->extension->supports('test', 'another')
        );
    }

    public function testGetFieldExtension()
    {
        self::assertSame(
            $this->transitionAclExtension,
            $this->extension->getFieldExtension()
        );
    }

    public function testGetClasses()
    {
        $classes = [new WorkflowAclMetadata('workflow1')];

        $this->workflowMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn($classes);

        self::assertEquals(
            $classes,
            $this->extension->getClasses()
        );
    }

    public function testGetDefaultPermission()
    {
        self::assertSame('', $this->extension->getDefaultPermission());
    }

    /**
     * @dataProvider getPermissionGroupMaskProvider
     */
    public function testGetPermissionGroupMask(int $mask, ?int $expectedPermissionGroupMask)
    {
        self::assertSame($expectedPermissionGroupMask, $this->extension->getPermissionGroupMask($mask));
    }

    public function getPermissionGroupMaskProvider(): array
    {
        return [
            [0, null],
            [WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_BASIC, WorkflowMaskBuilder::GROUP_VIEW_WORKFLOW],
            [WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_LOCAL, WorkflowMaskBuilder::GROUP_VIEW_WORKFLOW],
            [WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_DEEP, WorkflowMaskBuilder::GROUP_VIEW_WORKFLOW],
            [WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_GLOBAL, WorkflowMaskBuilder::GROUP_VIEW_WORKFLOW],
            [WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_SYSTEM, WorkflowMaskBuilder::GROUP_VIEW_WORKFLOW],
            [WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_BASIC, WorkflowMaskBuilder::GROUP_PERFORM_TRANSITIONS],
            [WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_LOCAL, WorkflowMaskBuilder::GROUP_PERFORM_TRANSITIONS],
            [WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_DEEP, WorkflowMaskBuilder::GROUP_PERFORM_TRANSITIONS],
            [WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_GLOBAL, WorkflowMaskBuilder::GROUP_PERFORM_TRANSITIONS],
            [WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM, WorkflowMaskBuilder::GROUP_PERFORM_TRANSITIONS]
        ];
    }

    public function testGetAllowedPermissions()
    {
        self::assertEquals(
            ['VIEW_WORKFLOW', 'PERFORM_TRANSITIONS'],
            $this->extension->getAllowedPermissions(new ObjectIdentity('1', 'test'))
        );
    }

    public function testGetObjectIdentity()
    {
    }

    public function testGetMaskPattern()
    {
        self::assertEquals(self::PATTERN_ALL_OFF, $this->extension->getMaskPattern(0));
    }

    public function testGetMaskBuilder()
    {
        self::assertInstanceOf(
            WorkflowMaskBuilder::class,
            $this->extension->getMaskBuilder('PERFORM_TRANSITIONS')
        );
    }

    public function testGetAllMaskBuilders()
    {
        $maskBuilders = $this->extension->getAllMaskBuilders();
        self::assertCount(1, $maskBuilders);
        self::assertInstanceOf(
            WorkflowMaskBuilder::class,
            $maskBuilders[0]
        );
    }

    /**
     * @dataProvider getServiceBitsProvider
     */
    public function testGetServiceBits(int $mask, int $expectedMask)
    {
        self::assertEquals($expectedMask, $this->extension->getServiceBits($mask));
    }

    public function getServiceBitsProvider(): array
    {
        return [
            'zero mask'                        => [
                WorkflowMaskBuilder::GROUP_NONE,
                WorkflowMaskBuilder::GROUP_NONE
            ],
            'not zero mask'                    => [
                WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM,
                WorkflowMaskBuilder::GROUP_NONE
            ],
            'zero mask, not zero identity'     => [
                WorkflowMaskBuilder::REMOVE_SERVICE_BITS + 1,
                WorkflowMaskBuilder::REMOVE_SERVICE_BITS + 1
            ],
            'not zero mask, not zero identity' => [
                (WorkflowMaskBuilder::REMOVE_SERVICE_BITS + 1) | WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM,
                WorkflowMaskBuilder::REMOVE_SERVICE_BITS + 1
            ],
        ];
    }

    /**
     * @dataProvider removeServiceBitsProvider
     */
    public function testRemoveServiceBits(int $mask, int $expectedMask)
    {
        self::assertEquals($expectedMask, $this->extension->removeServiceBits($mask));
    }

    public function removeServiceBitsProvider(): array
    {
        return [
            'zero mask'                        => [
                WorkflowMaskBuilder::GROUP_NONE,
                WorkflowMaskBuilder::GROUP_NONE
            ],
            'not zero mask'                    => [
                WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM,
                WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM
            ],
            'zero mask, not zero identity'     => [
                WorkflowMaskBuilder::REMOVE_SERVICE_BITS + 1,
                WorkflowMaskBuilder::GROUP_NONE
            ],
            'not zero mask, not zero identity' => [
                (WorkflowMaskBuilder::REMOVE_SERVICE_BITS + 1) | WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM,
                WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM
            ],
        ];
    }

    /**
     * @dataProvider notSupportedObjectProvider
     */
    public function testDecideIsGrantingForNotSupportedObject(mixed $object)
    {
        $securityToken = $this->createMock(TokenInterface::class);
        self::assertTrue($this->extension->decideIsGranting(0, $object, $securityToken));
    }

    public function notSupportedObjectProvider(): array
    {
        return [
            ['test'],
            [new ObjectIdentity('1', 'test')]
        ];
    }

    public function testDecideIsGrantingForDomainObjectReference()
    {
        $object = new DomainObjectReference('workflow1', 123, 1, 2);
        $relatedEntity = new \stdClass();
        $securityToken = $this->createMock(TokenInterface::class);

        $workflow = $this->createMock(Workflow::class);
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflow->expects(self::once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflowDefinition->expects(self::once())
            ->method('getRelatedEntity')
            ->willReturn($relatedEntity);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(self::identicalTo($relatedEntity))
            ->willReturn(new OwnershipMetadata());
        $this->workflowManager->expects(self::once())
            ->method('getWorkflow')
            ->willReturn($workflow);

        self::assertTrue(
            $this->extension->decideIsGranting(0, $object, $securityToken)
        );
    }

    public function testDecideIsGrantingForNoOwningObject()
    {
        $object = new DomainObjectStub();
        $securityToken = $this->createMock(TokenInterface::class);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(get_class($object))
            ->willReturn(new OwnershipMetadata());

        self::assertTrue(
            $this->extension->decideIsGranting(0, $object, $securityToken)
        );
    }

    public function testDecideIsGrantingForUserOwningObject()
    {
        $object = new DomainObjectStub();
        $securityToken = $this->createMock(TokenInterface::class);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(get_class($object))
            ->willReturn(new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org'));
        $this->decisionMaker->expects(self::once())
            ->method('isAssociatedWithUser')
            ->willReturn(true);

        self::assertTrue(
            $this->extension->decideIsGranting(
                WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_BASIC,
                $object,
                $securityToken
            )
        );
    }

    /**
     * @dataProvider validateMaskForRootWithSystemAccessLevelProvider
     */
    public function testValidateMaskForRootWithSystemAccessLevel(int $mask)
    {
        $this->metadataProvider->expects($this->any())
            ->method('getMaxAccessLevel')
            ->willReturn(AccessLevel::SYSTEM_LEVEL);
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn(new RootOwnershipMetadata());

        $this->extension->validateMask(
            $mask,
            new ObjectIdentity('workflow', ObjectIdentityFactory::ROOT_IDENTITY_TYPE)
        );
    }

    /**
     * @dataProvider validateMaskForRootWithoutSystemAccessLevelProvider
     */
    public function testValidateMaskForRootWithoutSystemAccessLevel(int $mask)
    {
        $this->metadataProvider->expects($this->any())
            ->method('getMaxAccessLevel')
            ->willReturn(AccessLevel::GLOBAL_LEVEL);
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn(new RootOwnershipMetadata());

        $this->extension->validateMask(
            $mask,
            new ObjectIdentity('workflow', ObjectIdentityFactory::ROOT_IDENTITY_TYPE)
        );
    }

    /**
     * @dataProvider validateMaskForRootWithoutSystemAccessLevelAndInvalidMasksProvider
     */
    public function testValidateMaskForRootWithoutSystemAccessLevelAndInvalidMasks(int $mask, string $expectedException)
    {
        $this->expectException(InvalidAclMaskException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid ACL mask "%s" for ObjectIdentity(workflow, (root)).',
            $expectedException
        ));

        $this->metadataProvider->expects($this->any())
            ->method('getMaxAccessLevel')
            ->willReturn(AccessLevel::GLOBAL_LEVEL);

        $this->extension->validateMask(
            $mask,
            new ObjectIdentity('workflow', ObjectIdentityFactory::ROOT_IDENTITY_TYPE)
        );
    }

    public static function validateMaskForRootWithSystemAccessLevelProvider(): array
    {
        return [
            [1 << 0 /* MASK_VIEW_WORKFLOW_BASIC */],
            [1 << 1 /* MASK_PERFORM_TRANSITIONS_BASIC */],
            [1 << 2 /* MASK_VIEW_WORKFLOW_LOCAL */],
            [1 << 3 /* MASK_PERFORM_TRANSITIONS_LOCAL */],
            [1 << 4 /* MASK_VIEW_WORKFLOW_DEEP */],
            [1 << 5 /* MASK_PERFORM_TRANSITIONS_DEEP */],
            [1 << 6 /* MASK_VIEW_WORKFLOW_GLOBAL */],
            [1 << 7 /* MASK_PERFORM_TRANSITIONS_GLOBAL */],
            [1 << 8 /* MASK_VIEW_WORKFLOW_SYSTEM */],
            [1 << 9 /* MASK_PERFORM_TRANSITIONS_SYSTEM */]
        ];
    }

    public static function validateMaskForRootWithoutSystemAccessLevelProvider(): array
    {
        return [
            [1 << 0 /* MASK_VIEW_WORKFLOW_BASIC */],
            [1 << 1 /* MASK_PERFORM_TRANSITIONS_BASIC */],
            [1 << 2 /* MASK_VIEW_WORKFLOW_LOCAL */],
            [1 << 3 /* MASK_PERFORM_TRANSITIONS_LOCAL */],
            [1 << 4 /* MASK_VIEW_WORKFLOW_DEEP */],
            [1 << 5 /* MASK_PERFORM_TRANSITIONS_DEEP */],
            [1 << 6 /* MASK_VIEW_WORKFLOW_GLOBAL */],
            [1 << 7 /* MASK_PERFORM_TRANSITIONS_GLOBAL */],
        ];
    }

    public static function validateMaskForRootWithoutSystemAccessLevelAndInvalidMasksProvider(): array
    {
        return [
            [1 << 8 /* MASK_VIEW_WORKFLOW_SYSTEM */, '(PV) system:.V global:.. deep:.. local:.. basic:..'],
            [1 << 9 /* MASK_PERFORM_TRANSITIONS_SYSTEM */, '(PV) system:P. global:.. deep:.. local:.. basic:..']
        ];
    }
}
