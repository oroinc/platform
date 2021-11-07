<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\GridParams;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\GridParams\GridParamsExtension;

class GridParamsExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var GridParamsExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension  = new GridParamsExtension();
        $this->extension->setParameters(new ParameterBag());
    }

    /**
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(array $input, bool $result)
    {
        $this->assertEquals(
            $result,
            $this->extension->isApplicable(DatagridConfiguration::create($input))
        );
    }

    public function isApplicableProvider(): array
    {
        return [
            'applicable' => [
                'input' => [
                    'source' => [
                        'type' => 'orm'
                    ]
                ],
                'result' => true
            ],
            'not applicable' => [
                'input' => [
                    'source' => [
                        'type' => 'not_orm'
                    ]
                ],
                'result' => false
            ]
        ];
    }

    /**
     * @dataProvider visitMetadataProvider
     */
    public function testVisitMetadata(array $parameters, array $gridParameters)
    {
        $dataGridConfig = $this->createMock(DatagridConfiguration::class);
        $metadata = $this->createMock(MetadataObject::class);

        $this->extension->setParameters(new ParameterBag($parameters));

        $metadata->expects($this->once())
            ->method('offsetAddToArray')
            ->with('gridParams', $gridParameters);

        $this->extension->visitMetadata($dataGridConfig, $metadata);
    }

    public function visitMetadataProvider(): array
    {
        return [
            'with grid params' => [
                'parameters' => [
                    ParameterBag::MINIFIED_PARAMETERS => ['i' => '1', 'p' => '25', 's'=> ['test_field' => '1']],
                    ParameterBag::ADDITIONAL_PARAMETERS => [],
                    'class_name' => 'Extend\Entity\Test'
                ],
                'gridParams' => ['class_name' => 'Extend\Entity\Test']
            ],
            'without grid params' => [
                'parameters' => [
                    ParameterBag::MINIFIED_PARAMETERS => ['i' => '1', 'p' => '25', 's'=> ['test_field' => '1']],
                    ParameterBag::ADDITIONAL_PARAMETERS => [],
                ],
                'gridParams' => []
            ]
        ];
    }
}
