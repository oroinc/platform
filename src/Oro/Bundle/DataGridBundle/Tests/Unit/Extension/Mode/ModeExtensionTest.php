<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Mode;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Mode\ModeExtension;

class ModeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ModeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new ModeExtension();
        $this->extension->setParameters(new ParameterBag());
    }

    public function isApplicableDataProvider(): array
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
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(array $config, bool $isApplicable)
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
