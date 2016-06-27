<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Autocomplete;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

use Oro\Bundle\OrganizationBundle\Autocomplete\BusinessUnitSearchHandler;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class BusinessUnitSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|BusinessUnitSearchHandler */
    protected $businessUnitSearchHandler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ClassMetadataFactory */
    protected $classMetadataFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ClassMetadata */
    protected $classMetadata;

    public function setUp()
    {
        $this->manager = self::getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();

        $this->doctrine = self::getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()->getMock();

        $this->repository = self::getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()->getMock();

        $this->classMetadataFactory = self::getMockBuilder('\Doctrine\Common\Persistence\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()->getMock();

        $this->classMetadata = self::getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()->getMock();

        $this->businessUnitSearchHandler = new BusinessUnitSearchHandler('', [], $this->doctrine);
    }

    public function testCheckCorrectWork()
    {
        $businessUnit = $this->getBusinessUnit();

        $this->repository->expects(self::any())->method('find')->willReturn($businessUnit);
        $this->classMetadata->expects(self::once())->method('getSingleIdentifierFieldName')->willReturn('id');

        $this->classMetadataFactory->expects(self::once())->method('getMetadataFor')->willReturn($this->classMetadata);

        $this->manager->expects(self::any())->method('getRepository')->willReturn($this->repository);
        $this->manager->expects(self::once())->method('getMetadataFactory')->willReturn($this->classMetadataFactory);
        $this->doctrine->expects(self::once())->method('getManager')->willReturn($this->manager);

        $item=[];
        $this->businessUnitSearchHandler->initDoctrinePropertiesByEntityManager($this->manager);
        $response = $this->businessUnitSearchHandler->convertItem($item);

        self::assertEquals($this->getExpectedData(), $response);
    }

    /**
     * @return array
     */
    protected function getExpectedData()
    {
        return [
            'id'=>null,
            'treePath' => [
                [
                    'name' => 'Org 1'
                ],[
                    'name' => 'BU_1'
                ],[
                    'name' => 'BU_1_1'
                ]
            ],
            'organization_id' => null
        ];
    }

    /**
     * @return BusinessUnit
     */
    protected function getBusinessUnit()
    {
        $organization = new Organization();
        $organization->setName('Org 1');

        $businessUnit = new BusinessUnit();
        $businessUnit->setName('BU_1');
        $businessUnit->setOrganization($organization);

        $businessUnit1 = new BusinessUnit();
        $businessUnit1->setName('BU_1_1');
        $businessUnit1->setOwner($businessUnit);
        $businessUnit1->setOrganization($organization);

        return $businessUnit1;
    }
}
