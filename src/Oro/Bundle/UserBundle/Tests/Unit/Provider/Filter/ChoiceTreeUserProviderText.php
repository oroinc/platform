<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider;

class ChoiceTreeUserProviderText extends \PHPUnit_Framework_TestCase
{
    /** @var ChoiceTreeBusinessUnitProvider */
    protected $choiceTreeBusinessUnitProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->choiceTreeBusinessUnitProvider = new ChoiceTreeBusinessUnitProvider(
            $this->registry,
            $this->securityFacade,
            $this->aclHelper
        );

        $this->registry->getRepository('OroOrganizationBundle:BusinessUnit');
    }

    public function testGetList()
    {
        $businessUnitRepository = $this->getMockBuilder('BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getRootBusinessUnits', 'getChildBusinessUnits'])
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getResult'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper->expects($this->any())->method('apply')->with($qb1)->willReturn($qb1);

        $businessUnitRepository->expects($this->any())->method('getRootBusinessUnits')->willReturn($qb1);


        $this->aclHelper->expects($this->any())->method('apply')->with($qb)->willReturn($qb);
        $qb->expects($this->any())->method('getResult')->willReturn($this->getTestBusinessUnits());


        $this->registry->expects($this->once())->method('getRepository')->with('OroOrganizationBundle:BusinessUnit')
            ->willReturn($businessUnitRepository);

        $result = $this->choiceTreeBusinessUnitProvider->getList();

        $this->assertEquals([], $result);
    }

    protected function getTestBusinessUnits()
    {
        $rootBusinessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $rootBusinessUnit->expects($this->any())->method('getId')->willReturn('1');
        $rootBusinessUnit->expects($this->any())->method('getOwner')->willReturn(null);
        $rootBusinessUnit->expects($this->any())->method('getName')->willReturn('Main Business Unit');

        $data = [
            [
                'name' => '2',
                'id' => '2',
                'owner_id' => $rootBusinessUnit
            ]
        ];

        $response = [];
        foreach($data as $item) {
            $businessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
                ->disableOriginalConstructor()
                ->getMock();
            $businessUnit->expects($this->any())->method('getId')->willReturn($item['id']);
            $businessUnit->expects($this->any())->method('getOwner')->willReturn($item['owner_id']);
            $businessUnit->expects($this->any())->method('getName')->willReturn($item['name']);


            $response[] = $businessUnit;
        }

        return $response;
    }

    protected function getRootBusinessUnits()
    {
        $rootBusinessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $rootBusinessUnit->expects($this->any())->method('getId')->willReturn('1');
        $rootBusinessUnit->expects($this->any())->method('getOwner')->willReturn(null);
        $rootBusinessUnit->expects($this->any())->method('getName')->willReturn('Main Business Unit');

        $response = [$rootBusinessUnit];

        return $response;
    }
}
