<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider\Filter;

use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider;

class ChoiceTreeBusinessUnitProviderTest extends \PHPUnit_Framework_TestCase
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
    }

    public function testGetList()
    {
        $businessUnitRepository = $this->getMockBuilder('BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getQueryBuilder'])
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getResult'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper->expects($this->any())->method('apply')->willReturn($qb);
        $businessUnitRepository->expects($this->any())->method('getQueryBuilder')->willReturn($qb);
        $qb->expects($this->any())->method('getResult')->willReturn($this->getTestBusinessUnits());

        $this->registry->expects($this->once())->method('getRepository')->with('OroOrganizationBundle:BusinessUnit')
            ->willReturn($businessUnitRepository);

        $result = $this->choiceTreeBusinessUnitProvider->getList();

        $this->assertEquals($this->getExpectedData(), $result);
    }

    public function testGetEmptyList()
    {
        $businessUnitRepository = $this->getMockBuilder('BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getQueryBuilder'])
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getResult'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper->expects($this->any())->method('apply')->willReturn($qb);
        $businessUnitRepository->expects($this->any())->method('getQueryBuilder')->willReturn($qb);
        $qb->expects($this->any())->method('getResult')->willReturn([]);

        $this->registry->expects($this->once())->method('getRepository')->with('OroOrganizationBundle:BusinessUnit')
            ->willReturn($businessUnitRepository);

        $result = $this->choiceTreeBusinessUnitProvider->getList();

        $this->assertEquals([], $result);
    }

    protected function getTestBusinessUnits()
    {
        $data = [
            [
                'name' => 'Main Business Unit',
                'id' => 1,
                'owner_id' => null
            ],
            [
                'name' => 'Business Uit 1',
                'id' => 2,
                'owner_id' => $this->getTestDataRootBusinessUnit()
            ]
        ];

        return $this->convertTestDataToBusinessUnitEntity($data);
    }

    protected function convertTestDataToBusinessUnitEntity($data)
    {
        $response = [];
        foreach ($data as $item) {
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

    protected function getTestDataRootBusinessUnit()
    {
        $rootBusinessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $rootBusinessUnit->expects($this->any())->method('getId')->willReturn('1');
        $rootBusinessUnit->expects($this->any())->method('getOwner')->willReturn(null);
        $rootBusinessUnit->expects($this->any())->method('getName')->willReturn('Main Business Unit');

        return $rootBusinessUnit;
    }

    protected function getExpectedData()
    {
        return [
            [
                'name' => 'Main Business Unit',
                'id' => 1,
                'owner_id' => null
            ],
            [
                'name' => 'Business Uit 1',
                'id' => 2,
                'owner_id' => 1
            ]
        ];
    }
}
