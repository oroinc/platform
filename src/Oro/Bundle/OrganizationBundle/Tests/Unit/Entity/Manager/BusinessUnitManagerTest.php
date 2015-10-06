<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

class BusinessUnitManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $buRepo;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $userRepo;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;
    /**
     * @var BusinessUnitManager
     */
    protected $businessUnitManager;

    protected function setUp()
    {
        $this->buRepo = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with(
                $this->logicalOr(
                    $this->equalTo('OroOrganizationBundle:BusinessUnit'),
                    $this->equalTo('OroUserBundle:User')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($param) {
                        if ($param == 'OroOrganizationBundle:BusinessUnit') {
                            return $this->buRepo;
                        } else {
                            return $this->userRepo;
                        }
                    }
                )
            );

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->businessUnitManager = new BusinessUnitManager($this->em, $this->securityFacade, $this->aclHelper);
    }

    public function testGetTreeOptions()
    {
        $inputData = [
            [
                'id' => '3',
                'name' => 'First BU',
                'parent' => null,
                'organization' => 1,
                'children' => [
                    [
                        'id' => '5',
                        'name' => 'Sub First BU',
                        'parent' => null,
                        'organization' => 1,
                        'children' => [
                            [
                                'id' => '4',
                                'name' => 'Sub Sub First BU',
                                'parent' => null,
                                'organization' => 1,
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => '10',
                'name' => 'Second BU',
                'parent' => null,
                'organization' => 1,
                'children' => [
                    [
                        'id' => 11,
                        'name' => 'Sub Second BU',
                        'parent' => null,
                        'organization' => 1,
                    ]
                ],
            ],
            [
                'id' => '15',
                'name' => 'BU wo children',
                'parent' => null,
                'organization' => 1,
            ]
        ];
        $result = $this->businessUnitManager->getTreeOptions($inputData);
        $expectedResult = [
            '3'  => 'First BU',
            '5'  => '&nbsp;&nbsp;&nbsp;Sub First BU',
            '4'  => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sub Sub First BU',
            '10' => 'Second BU',
            '11' => '&nbsp;&nbsp;&nbsp;Sub Second BU',
            '15' => 'BU wo children'
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetBusinessUnitsTree()
    {
        $this->buRepo->expects($this->once())
            ->method('getBusinessUnitsTree');
        $this->businessUnitManager->getBusinessUnitsTree();
    }

    public function getBusinessUnitIds()
    {
        $this->buRepo->expects($this->once())
            ->method('getBusinessUnitIds');
        $this->businessUnitManager->getBusinessUnitIds();
    }

    public function testGetBusinessUnit()
    {
        $this->buRepo->expects($this->once())
            ->method('findOneBy');
        $this->businessUnitManager->getBusinessUnit();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCanUserBeSetAsOwner($currentUser, $newUser, $accessLevel, $organizationContext, $isCanBeSet)
    {
        $tree = new OwnerTree();
        $this->addUserInfoToTree($tree, $currentUser);
        $this->addUserInfoToTree($tree, $newUser);

        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProvider->expects($this->any())
            ->method('getTree')
            ->will($this->returnValue($tree));

        $result = $this->businessUnitManager->canUserBeSetAsOwner(
            $currentUser,
            $newUser,
            $accessLevel,
            $treeProvider,
            $organizationContext
        );
        $this->assertEquals($isCanBeSet, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function dataProvider()
    {
        $organization1 = new Organization();
        $organization1->setId(1);

        $organization2 = new Organization();
        $organization2->setId(2);

        $bu11 = new BusinessUnit();
        $bu11->setId(1);
        $bu11->setOrganization($organization1);

        $bu22 = new BusinessUnit();
        $bu22->setId(2);
        $bu22->setOrganization($organization2);

        $newUser = new User();
        $newUser->setId(2);
        $newUser->setOrganizations(new ArrayCollection([$organization1]));
        $newUser->setBusinessUnits(new ArrayCollection([$bu11]));

        return [
            'BASIC_LEVEL access level, current user' => [
                $this->getCurrentUser(2, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::BASIC_LEVEL,
                $organization1,
                true
            ],
            'BASIC_LEVEL access level, another user' => [
                $this->getCurrentUser(1, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::BASIC_LEVEL,
                $organization1,
                false
            ],
            'SYSTEM_LEVEL access level, current user' => [
                $this->getCurrentUser(2, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::SYSTEM_LEVEL,
                $organization1,
                true
            ],
            'SYSTEM_LEVEL access level, another user' => [
                $this->getCurrentUser(1, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::SYSTEM_LEVEL,
                $organization1,
                true
            ],
            'GLOBAL_LEVEL access level, current user' => [
                $this->getCurrentUser(2, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::GLOBAL_LEVEL,
                $organization1,
                true
            ],
            'GLOBAL_LEVEL access level, another user, same org' => [
                $this->getCurrentUser(1, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::GLOBAL_LEVEL,
                $organization1,
                true
            ],
            'GLOBAL_LEVEL access level, another user, different org' => [
                $this->getCurrentUser(1, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::GLOBAL_LEVEL,
                $organization2,
                false
            ],
            'LOCAL_LEVEL access level, current user' => [
                $this->getCurrentUser(2, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::LOCAL_LEVEL,
                $organization1,
                true
            ],
            'LOCAL_LEVEL access level, another user, same org' => [
                $this->getCurrentUser(1, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::LOCAL_LEVEL,
                $organization1,
                true
            ],
            'LOCAL_LEVEL access level, another user, different org' => [
                $this->getCurrentUser(1, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::LOCAL_LEVEL,
                $organization2,
                false
            ],
            'DEEP_LEVEL access level, current user' => [
                $this->getCurrentUser(2, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::DEEP_LEVEL,
                $organization1,
                true
            ],
            'DEEP_LEVEL access level, another user, same org' => [
                $this->getCurrentUser(1, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::DEEP_LEVEL,
                $organization1,
                true
            ],
            'DEEP_LEVEL access level, another user, different org' => [
                $this->getCurrentUser(1, [$organization1], [$bu11]),
                $newUser,
                AccessLevel::DEEP_LEVEL,
                $organization2,
                false
            ],
        ];
    }

    /**
     * @param int   $id
     * @param array $organizations
     * @param array $bUnits
     * @return User
     */
    protected function getCurrentUser($id, array $organizations, array $bUnits)
    {
        $user = new User();
        $user->setId($id);
        $user->setBusinessUnits(new ArrayCollection($bUnits));
        $user->setOrganizations(new ArrayCollection($organizations));

        return $user;
    }

    protected function addUserInfoToTree(OwnerTree $tree, User $user)
    {
        $owner = $user->getOwner();
        $tree->addUser($user->getId(), $owner ? $owner->getId() : null);
        foreach ($user->getOrganizations() as $organization) {
            $tree->addUserOrganization($user->getId(), $organization->getId());
            foreach ($user->getBusinessUnits() as $businessUnit) {
                $organizationId   = $organization->getId();
                $buOrganizationId = $businessUnit->getOrganization()->getId();
                if ($organizationId == $buOrganizationId) {
                    $tree->addUserBusinessUnit($user->getId(), $organizationId, $businessUnit->getId());
                }
            }
        }
    }
}
