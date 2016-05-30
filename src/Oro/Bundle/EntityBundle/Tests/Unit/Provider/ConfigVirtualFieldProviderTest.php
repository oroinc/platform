<?php
namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ConfigVirtualFieldProvider;

class ConfigVirtualFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigVirtualFieldProvider */
    private $configVirtualFieldProvider;

    /** @var array configuration */
    private $configurationVirtualFields;

    protected function setUp()
    {
        $entityHierarchyProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface');

        $hierarchy = ['TestEntity' => ['AbstractEntity']];
        $entityHierarchyProvider
            ->expects($this->any())
            ->method('getHierarchy')
            ->will($this->returnValue($hierarchy));

        $this->configurationVirtualFields = [
            'AbstractEntity' => [
                'virtual_field' => [
                    'query' => [
                        'select' => ['select expression config ...'],
                        'join' => ['join expression config ...']
                    ]
                ]
            ]
        ];

        $this->configVirtualFieldProvider = new ConfigVirtualFieldProvider(
            $entityHierarchyProvider,
            $this->configurationVirtualFields
        );
    }

    public function testGetVirtualFields()
    {
        $this->assertEquals(
            ['virtual_field'],
            $this->configVirtualFieldProvider->getVirtualFields('TestEntity')
        );
        $this->assertEquals(
            [],
            $this->configVirtualFieldProvider->getVirtualFields('EntityWithoutVirtualFields')
        );
    }

    public function testIsVirtualField()
    {
        $this->assertTrue($this->configVirtualFieldProvider->isVirtualField('TestEntity', 'virtual_field'));
        $this->assertFalse($this->configVirtualFieldProvider->isVirtualField('TestEntity', 'non_virtual_field'));
    }

    public function testGetVirtualFieldQuery()
    {
        $this->assertEquals(
            $this->configurationVirtualFields['AbstractEntity']['virtual_field']['query'],
            $this->configVirtualFieldProvider->getVirtualFieldQuery('TestEntity', 'virtual_field')
        );
    }
}
