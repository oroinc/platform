<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension\Stub\DomainObjectStub;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclExtension;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionAclExtension;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionMaskBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowTransitionAclExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $objectIdAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityOwnerAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $decisionMaker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    /** @var WorkflowTransitionAclExtension */
    protected $extension;

    protected function setUp()
    {
        $this->objectIdAccessor = $this->getMockBuilder(ObjectIdAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMock(MetadataProviderInterface::class);
        $this->entityOwnerAccessor = $this->getMockBuilder(EntityOwnerAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->decisionMaker = $this->getMock(AccessLevelOwnershipDecisionMakerInterface::class);
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new WorkflowTransitionAclExtension(
            $this->objectIdAccessor,
            $this->metadataProvider,
            $this->entityOwnerAccessor,
            $this->decisionMaker,
            $this->workflowRegistry
        );
    }

    public function testGetExtensionKey()
    {
        $this->assertEquals(WorkflowAclExtension::NAME, $this->extension->getExtensionKey());
    }

    public function testSupports()
    {
        self::assertTrue($this->extension->supports('', ''));
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetClasses()
    {
        $this->extension->getClasses();
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetObjectIdentity()
    {
        $this->extension->getObjectIdentity('');
    }

    public function testGetAllowedPermissions()
    {
        self::assertEquals(
            ['PERFORM_TRANSITION'],
            $this->extension->getAllowedPermissions(new ObjectIdentity('1', 'test'))
        );
    }

    public function testGetMaskPattern()
    {
        self::assertEquals(
            WorkflowTransitionMaskBuilder::PATTERN_ALL_OFF,
            $this->extension->getMaskPattern(0)
        );
    }

    public function testGetMaskBuilder()
    {
        self::assertInstanceOf(
            WorkflowTransitionMaskBuilder::class,
            $this->extension->getMaskBuilder('PERFORM_TRANSITION')
        );
    }

    public function testGetAllMaskBuilders()
    {
        $maskBuilders = $this->extension->getAllMaskBuilders();
        self::assertCount(1, $maskBuilders);
        self::assertInstanceOf(
            WorkflowTransitionMaskBuilder::class,
            $maskBuilders[0]
        );
    }

    /**
     * @dataProvider getServiceBitsProvider
     */
    public function testGetServiceBits($mask, $expectedMask)
    {
        self::assertEquals($expectedMask, $this->extension->getServiceBits($mask));
    }

    public function getServiceBitsProvider()
    {
        return [
            'zero mask'                        => [
                WorkflowTransitionMaskBuilder::GROUP_NONE,
                WorkflowTransitionMaskBuilder::GROUP_NONE
            ],
            'not zero mask'                    => [
                WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM,
                WorkflowTransitionMaskBuilder::GROUP_NONE
            ],
            'zero mask, not zero identity'     => [
                WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS + 1,
                WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS + 1
            ],
            'not zero mask, not zero identity' => [
                (WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS + 1)
                | WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM,
                WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS + 1
            ],
        ];
    }

    /**
     * @dataProvider removeServiceBitsProvider
     */
    public function testRemoveServiceBits($mask, $expectedMask)
    {
        self::assertEquals($expectedMask, $this->extension->removeServiceBits($mask));
    }

    public function removeServiceBitsProvider()
    {
        return [
            'zero mask'                        => [
                WorkflowTransitionMaskBuilder::GROUP_NONE,
                WorkflowTransitionMaskBuilder::GROUP_NONE
            ],
            'not zero mask'                    => [
                WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM,
                WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM
            ],
            'zero mask, not zero identity'     => [
                WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS + 1,
                WorkflowTransitionMaskBuilder::GROUP_NONE
            ],
            'not zero mask, not zero identity' => [
                (WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS + 1)
                | WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM,
                WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM
            ],
        ];
    }

    /**
     * @dataProvider notSupportedObjectProvider
     */
    public function testDecideIsGrantingForNotSupportedObject($object)
    {
        $securityToken = $this->getMock(TokenInterface::class);
        self::assertTrue($this->extension->decideIsGranting(0, $object, $securityToken));
    }

    public function notSupportedObjectProvider()
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
        $securityToken = $this->getMock(TokenInterface::class);

        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflowDefinition = $this->getMockBuilder(WorkflowDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $this->workflowRegistry->expects(self::once())
            ->method('getWorkflow')
            ->willReturn('workflow1')
            ->willReturn($workflow);

        self::assertTrue(
            $this->extension->decideIsGranting(0, $object, $securityToken)
        );
    }

    public function testDecideIsGrantingForNoOwningObject()
    {
        $object = new DomainObjectStub();
        $securityToken = $this->getMock(TokenInterface::class);

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
        $securityToken = $this->getMock(TokenInterface::class);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(get_class($object))
            ->willReturn(new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org'));
        $this->decisionMaker->expects(self::once())
            ->method('isAssociatedWithBasicLevelEntity')
            ->willReturn(true);

        self::assertTrue(
            $this->extension->decideIsGranting(
                WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_BASIC,
                $object,
                $securityToken
            )
        );
    }
}
