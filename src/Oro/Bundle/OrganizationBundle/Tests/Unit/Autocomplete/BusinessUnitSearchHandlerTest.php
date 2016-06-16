<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\OrganizationBundle\Autocomplete\BusinessUnitSearchHandler;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class BusinessUnitSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testCheckCorrectWork()
    {
        $manager = self::getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();

        $doctrine = self::getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()->getMock();

        $repository = self::getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()->getMock();

        $classMetadataFactory = self::getMockBuilder('\Doctrine\Common\Persistence\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()->getMock();

        $classMetadata = self::getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()->getMock();
        $classMetadata->expects(self::once())->method('getSingleIdentifierFieldName')->willReturn('id');

        $classMetadataFactory->expects(self::once())->method('getMetadataFor')->willReturn($classMetadata);

        $manager->expects(self::any())->method('getRepository')->willReturn($repository);
        $manager->expects(self::once())->method('getMetadataFactory')->willReturn($classMetadataFactory);
        $doctrine->expects(self::once())->method('getManager')->willReturn($manager);


        $businessUnit = new BusinessUnit();
        $businessUnit->setName('BU_1');
        $businessUnit1 = new BusinessUnit();
        $businessUnit1->setName('BU_1_1');

        $businessUnit1->setOwner($businessUnit);

        $repository->expects(self::any())->method('find')->willReturn($businessUnit1);
        $businessUnitSearchHandler = new BusinessUnitSearchHandler('', [], $doctrine);

        $item=[];

        $businessUnitSearchHandler->initDoctrinePropertiesByEntityManager($manager);
        $rsponse = $businessUnitSearchHandler->convertItem($item);


        self::assertEquals($this->getExpectedData(), $rsponse);
    }

    protected function getExpectedData()
    {
        return [
            'id'=>null,
            'treePath' => [
                [
                    'name' => 'BU_1'
                ],
                [
                    'name' => 'BU_1_1'
                ]
            ]
        ];
    }
}
