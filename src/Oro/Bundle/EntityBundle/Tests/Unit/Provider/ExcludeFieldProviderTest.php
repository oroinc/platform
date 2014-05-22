<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ExcludeFieldProvider;

class ExcludeFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExcludeFieldProvider */
    protected $provider;

    public function setUp()
    {
        $hierarchyProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $hierarchyProvider->expects($this->any())
            ->method('getHierarchyForClassName')
            ->with('Test\Entity\Address')
            ->will($this->returnValue(['Test\Entity\AbstractAddress']));

        $this->provider = new ExcludeFieldProvider(
            $hierarchyProvider,
            [
                ['entity' => 'Test\Entity\Address', 'field' => 'field1'],
                ['entity' => 'Test\Entity\AbstractAddress', 'field' => 'field2'],
                ['type' => 'date']
            ]
        );
    }

    public function testIsIgnoredField()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Test\Entity\Address'));

        $metadata->expects($this->any())
            ->method('getTypeOfField')
            ->will(
                $this->returnValueMap(
                    [
                        ['field1', 'integer'],
                        ['field2', 'string'],
                        ['field3', 'date'],
                        ['field4', 'text'],
                    ]
                )
            );

        $this->assertTrue(
            $this->provider->isIgnoreField($metadata, 'field1')
        );
        $this->assertTrue(
            $this->provider->isIgnoreField($metadata, 'field2')
        );
        $this->assertTrue(
            $this->provider->isIgnoreField($metadata, 'field3')
        );
        $this->assertFalse(
            $this->provider->isIgnoreField($metadata, 'field4')
        );
    }
}
