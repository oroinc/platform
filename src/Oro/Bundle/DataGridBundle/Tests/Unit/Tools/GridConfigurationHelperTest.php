<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class GridConfigurationHelperTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\DatagridBundle\Tests\Unit\Stub\SomeEntity';
    const ENTITY_ALIAS = 'c';

    /** @var GridConfigurationHelper */
    protected $gridConfigurationHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassResolver;

    protected function setUp()
    {
        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(self::ENTITY_CLASS);

        $this->gridConfigurationHelper = new GridConfigurationHelper($this->entityClassResolver);
    }

    /**
     * @dataProvider getDatagridConfigurationDataProvider
     *
     * @param array $param
     */
    public function testGetEntity($param)
    {
        $config = DatagridConfiguration::create($param);
        $this->assertEquals(self::ENTITY_CLASS, $this->gridConfigurationHelper->getEntity($config));
    }

    /**
     * @dataProvider getDatagridConfigurationDataProvider
     *
     * @param array $param
     */
    public function testGetRootAlias($param)
    {
        $config = DatagridConfiguration::create($param);
        $this->assertEquals(self::ENTITY_ALIAS, $this->gridConfigurationHelper->getEntityRootAlias($config));
    }

    public function getDatagridConfigurationDataProvider()
    {
        $source = [
            'query' => [
                'from' => [
                    [
                        'table' => self::ENTITY_CLASS,
                        'alias' => self::ENTITY_ALIAS
                    ]
                ]
            ]

        ];

        return [
            'with extended entity name'    => [
                [
                    'extended_entity_name' => self::ENTITY_CLASS,
                    'source'               => $source
                ]
            ],
            'without extended entity name' => [
                [
                    'source' => $source
                ]
            ],
        ];
    }
}
