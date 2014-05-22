<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;
use Oro\Bundle\EntityBundle\Provider\ExcludeFieldProvider;

class ExcludeFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityHierarchyProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $hierarchyProviderMock;

    /** @var ExcludeFieldProvider */
    protected $provider;

    public function setUp()
    {
        $this->hierarchyProviderMock = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ExcludeFieldProvider(
            $this->hierarchyProviderMock,
            [
                ['entity' => 'Oro\Bundle\AddressBundle\Entity\Address', 'field' => 'fakeField']
            ]
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
                ['entity' => 'Oro\Bundle\AddressBundle\Entity\AbstractAddress', 'field' => 'regionText'],
            ]
        );

        $this->assertTrue($result);
    }
}
