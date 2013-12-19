<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

class BusinessUnitManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $em;
    protected $buRepo;
    protected $userRepo;

    /**
     * @var BusinessUnitManager
     */
    protected $businessUnitManager;

    public function setUp()
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

        $this->businessUnitManager = new BusinessUnitManager($this->em);
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
    public function testCanUserBeSetAsOwner($accessLevel, $expected, $parameterts = [])
    {
        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $tree = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTree')
            ->disableOriginalConstructor()
            ->getMock();
        if (isset($parameterts['userBU'])) {
            $tree->expects($this->any())
                ->method('getUserBusinessUnitIds')
                ->will($this->returnValue($parameterts['userBU']));
        }
        if (isset($parameterts['userSubBU'])) {
            $tree->expects($this->any())
                ->method('getUserSubordinateBusinessUnitIds')
                ->will($this->returnValue($parameterts['userSubBU']));
        }
        if (isset($parameterts['orgBU'])) {
            $tree->expects($this->any())
                ->method('getOrganizationBusinessUnitIds')
                ->will($this->returnValue($parameterts['orgBU']));
        }
        if (isset($parameterts['userOrg'])) {
            $tree->expects($this->any())
                ->method('getBusinessUnitsIdByUserOrganizations')
                ->will($this->returnValue($parameterts['userOrg']));
        }
        $treeProvider->expects($this->any())
            ->method('getTree')
            ->will($this->returnValue($tree));

        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $owner = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userRepo->expects($this->any())
            ->method('find')
            ->will($this->returnValue($user));

        $user->expects($this->any())
            ->method('getOwner')
            ->will($this->returnValue($owner));

        if (isset($parameterts['ownerId'])) {
            $owner->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($parameterts['ownerId']));
        }

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(isset($parameterts['id'])? $parameterts['id'] : 1));

        $result = $this->businessUnitManager->canUserBeSetAsOwner($user, 1, $accessLevel, $treeProvider);
        $this->assertEquals($expected, $result);
    }

    public function dataProvider()
    {
        return [
            [AccessLevel::BASIC_LEVEL, true],
            [AccessLevel::BASIC_LEVEL, false, ['id' => 2]],
            [AccessLevel::SYSTEM_LEVEL, true],
            [AccessLevel::LOCAL_LEVEL, true, ['userBU' => [1, 2, 3], 'ownerId' => 1]],
            [AccessLevel::LOCAL_LEVEL, false, ['userBU' => [2, 3], 'ownerId' => 1]],
            [AccessLevel::DEEP_LEVEL, true, ['userBU' => [1], 'userSubBU' => [1, 2], 'ownerId' => 2]],
            [AccessLevel::DEEP_LEVEL, false, ['userBU' => [1], 'userSubBU' => [2], 'ownerId' => 3]],
            [AccessLevel::GLOBAL_LEVEL, true, ['userOrg' => [1], 'orgBU' => [1, 2, 3], 'ownerId' => 1]],
            [AccessLevel::GLOBAL_LEVEL, false, ['userOrg' => [1], 'orgBU' => [1, 2, 3], 'ownerId' => 4]],
        ];
    }
}
