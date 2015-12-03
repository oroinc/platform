<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Mode;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\Mode\ModeExtension;

class ModeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ModeExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new ModeExtension();
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        return [
            'empty' => [
                'config' => [],
                'isApplicable' => false,
            ],
            'server' => [
                'config' => [
                    'options' => ['mode' => ModeExtension::MODE_SERVER],
                ],
                'isApplicable' => false,
            ],
            'client' => [
                'config' => [
                    'options' => ['mode' => ModeExtension::MODE_CLIENT],
                ],
                'isApplicable' => true,
            ],
        ];
    }

    /**
     * @param array $config
     * @param bool $isApplicable
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(array $config, $isApplicable)
    {
        $configObject = DatagridConfiguration::create($config);
        $this->assertSame($isApplicable, $this->extension->isApplicable($configObject));
    }

    public function testVisitMetadata()
    {
        $config = DatagridConfiguration::create(['options' => ['mode' => ModeExtension::MODE_CLIENT]]);
        $metadata = MetadataObject::create([]);

        $this->extension->visitMetadata($config, $metadata);
        $this->assertEquals(ModeExtension::MODE_CLIENT, $metadata->offsetGetByPath('mode'));
    }
}
