<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;
use Oro\Bundle\EntityBundle\Provider\ExcludeFieldProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;

class ExcludeFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityHierarchyProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $hierarchyProviderMock;

    /** @var EntityClassResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassResolver;

    /** @var ExcludeFieldProvider */
    protected $provider;

    public function setUp()
    {
        $this->hierarchyProviderMock = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver  = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ExcludeFieldProvider(
            $this->hierarchyProviderMock,
            [
                ['entity' => 'OroAddressBundle:Address', 'field' => 'fakeField']
            ],
            $this->entityClassResolver
        );
    }

    public function testIsIgnoredFieldTrue()
    {
        $metadata  = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->at(0))
            ->method('getName')
            ->will($this->returnValue('Oro\Bundle\AddressBundle\Entity\Address'));

        $metadata->expects($this->at(1))
            ->method('getTypeOfField')
            ->will($this->returnValue('integer'));

        $metadata->expects($this->at(2))
            ->method('getTypeOfField')
            ->will($this->returnValue('string'));

        $this->entityClassResolver->expects($this->at(0))
            ->method('getEntityClass')
            ->with('OroAddressBundle:Address')
            ->will($this->returnValue('Oro\Bundle\AddressBundle\Entity\Address'));

        $this->entityClassResolver->expects($this->at(1))
            ->method('getEntityClass')
            ->with('OroAddressBundle:AbstractAddress')
            ->will($this->returnValue('Oro\Bundle\AddressBundle\Entity\AbstractAddress'));

        $this->hierarchyProviderMock->expects($this->exactly(2))
            ->method('getHierarchyForClassName')
            ->with('Oro\Bundle\AddressBundle\Entity\Address')
            ->will(
                $this->returnValue(
                    [
                        'Oro\Bundle\AddressBundle\Entity\AbstractAddress',
                        'Oro\Bundle\AddressBundle\Entity\Address',
                    ]
                )
            );

        $result = $this->provider->isIgnoreField(
            $metadata,
            'regionText',
            [
                ['entity' => 'OroAddressBundle:AbstractAddress', 'field' => 'regionText'],
            ]
        );

        $this->assertTrue($result);
    }
}
