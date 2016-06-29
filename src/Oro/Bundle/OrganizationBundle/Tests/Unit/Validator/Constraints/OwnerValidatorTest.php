<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constrains;

use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\OwnerValidator;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;

class OwnerValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var OwnerValidator */
    protected $validator;

    /** @var Owner */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityOwnerAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $businessUnitManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclVoter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $treeProvider;

    /** @var Entity */
    protected $testEntity;

    /** @var User */
    protected $currentUser;

    /** @var Organization */
    protected $currentOrg;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownershipMetadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityOwnerAccessor = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->businessUnitManager = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new OwnerValidator(
            $this->registry,
            $this->businessUnitManager,
            $this->ownershipMetadataProvider,
            $this->entityOwnerAccessor,
            $this->securityFacade,
            $this->treeProvider,
            $this->aclVoter
        );

        $this->constraint = new Owner();
        $this->testEntity = new Entity();
        $this->currentUser = new User();
        $this->currentUser->setId(12);
        $this->currentOrg = new Organization();
        $this->currentOrg->setId(2);

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($this->currentUser);
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUserId')
            ->willReturn($this->currentUser->getId());
        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->currentOrg);
    }

    public function testValidateOnNonSupportedEntity()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(null);

        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->validator->validate($this->testEntity, $this->constraint);
    }

    public function testValidateOnNonACLProtectedEntity()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);

        $ownershipMetadata = new OwnershipMetadata();

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn($ownershipMetadata);

        $this->entityOwnerAccessor->expects($this->never())
            ->method('getOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
    }

    public function validationProvider()
    {
        $userOwner = new User();
        $userOwner->setId(123);

        $businessUnit = new BusinessUnit();
        $businessUnit->setId(345);

        $organization = new Organization();
        $organization->setId(34);

        return [
            'wrong owner, type User, Create'   => [$userOwner, 'USER', true, false],
            'correct owner, type User, Create' => [$userOwner, 'USER', true, true],
            'wrong owner, type User, Update'   => [$userOwner, 'USER', false, false],
            'correct owner, type User, Update' => [$userOwner, 'USER', false, true],

            'wrong owner, type Business Unit, Create'   => [$businessUnit, 'BUSINESS_UNIT', true, false],
            'correct owner, type Business Unit, Create' => [$businessUnit, 'BUSINESS_UNIT', true, true],
            'wrong owner, type Business Unit, Update'   => [$businessUnit, 'BUSINESS_UNIT', false, false],
            'correct owner, type Business Unit, Update' => [$businessUnit, 'BUSINESS_UNIT', false, true],

            'wrong owner, type Organization, Create'   => [$organization, 'ORGANIZATION', true, false],
            'correct owner, type Organization, Create' => [$organization, 'ORGANIZATION', true, true],
            'wrong owner, type Organization, Update'   => [$organization, 'ORGANIZATION', false, false],
            'correct owner, type Organization, Update' => [$organization, 'ORGANIZATION', false, true],
        ];
    }

    /**
     * @dataProvider validationProvider
     */
    public function testValidateCreatedEntityWithUserOwner($owner, $ownerType, $isCreate, $isOwnerCorrect)
    {
        $ownershipMetadata = new OwnershipMetadata($ownerType, 'owner', 'owner', 'organization', 'organization');
        $this->testEntity->setOwner($owner);
        $this->aclVoter->expects($this->once())
            ->method('addOneShotIsGrantedObserver')
            ->will(
                $this->returnCallback(
                    function ($input) {
                        $input->setAccessLevel(3);
                    }
                )
            );

        $classMetadata = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()->getMock();
        $om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();
        $om->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn($om);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with('Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn($ownershipMetadata);

        if ($isCreate) {
            $this->securityFacade->expects($this->once())
                ->method('isGranted')
                ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
                ->willReturn(true);
            $classMetadata->expects($this->once())->method('getIdentifierValues')->with($this->testEntity)
                ->willReturn([]);
        } else {
            $this->testEntity->setId(234);
            $this->securityFacade->expects($this->once())
                ->method('isGranted')
                ->with('ASSIGN', $this->testEntity)
                ->willReturn(true);
            $classMetadata->expects($this->once())->method('getIdentifierValues')->with($this->testEntity)
                ->willReturn([123]);
        }

        if (in_array($ownerType, ['USER', 'BUSINESS_UNIT'], true)) {
            $this->businessUnitManager->expects($this->once())
                ->method($ownerType === 'USER' ? 'canUserBeSetAsOwner' : 'canBusinessUnitBeSetAsOwner')
                ->with($this->currentUser, $owner, 3, $this->treeProvider, $this->currentOrg)
                ->willReturn($isOwnerCorrect);
        } else {
            $organizations = [2, 3];
            if ($isOwnerCorrect) {
                $organizations[] = $owner->getId();
            }
            $ownerTree = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTree')
                ->disableOriginalConstructor()
                ->getMock();
            $ownerTree->expects($this->once())
                ->method('getUserOrganizationIds')
                ->willReturn($organizations);
            $this->treeProvider->expects($this->once())
                ->method('getTree')
                ->willReturn($ownerTree);
        }

        $this->entityOwnerAccessor->expects($this->any())->method('getOwner')->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());
        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()->getMock();
        $violation = $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()->getMock();

        if ($isOwnerCorrect) {
            $violation->expects($this->never())->method('setParameter');
            $context->expects($this->never())->method('buildViolation');
        } else {
            $violation->expects($this->once())->method('setParameter')->willReturnSelf();
            $context->expects($this->once())
                ->method('buildViolation')
                ->with('You have no access to set this value as {{ owner }}.')
                ->willReturn($violation);
            $violation->expects($this->once())
                ->method('atPath')
                ->with('owner')
                ->willReturnSelf();
        }

        $this->validator->initialize($context);
        $this->validator->validate($this->testEntity, $this->constraint);
    }
}
