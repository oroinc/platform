<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\GridParams\GridParamsExtension;

class EntityPaginationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GridParamsExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension  = new GridParamsExtension();
    }

    /**
     * @param $input
     * @param $result
     *
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable($input, $result)
    {
        $this->assertEquals(
            $this->extension->isApplicable(
                DatagridConfiguration::create($input)
            ),
            $result
        );
    }

    /**
     * @return array
     */
    public function isApplicableProvider()
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
     * @param $parameters
     * @param $gridParameters
     * @dataProvider visitMetadataProvider
     */
    public function testVisitMetadata($parameters, $gridParameters)
    {
        $dataGridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension->setParameters(new ParameterBag($parameters));

        $metadata->expects($this->once())
            ->method('offsetAddToArray')
            ->with('gridParams', $gridParameters);

        $this->extension->visitMetadata($dataGridConfig, $metadata);
    }

    /**
     * @return array
     */
    public function visitMetadataProvider()
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
