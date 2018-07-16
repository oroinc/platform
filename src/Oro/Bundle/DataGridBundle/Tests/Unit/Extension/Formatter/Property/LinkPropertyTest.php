<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\LinkProperty;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;

class LinkPropertyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LinkProperty
     */
    protected $property;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $twig;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $router;

    protected function setUp()
    {
        $this->router = $this->createMock('Symfony\Component\Routing\RouterInterface');
        $this->twig = $this->createMock('Oro\Bundle\UIBundle\Twig\Environment');

        $this->property = new LinkProperty($this->router, $this->twig);
    }

    /**
     * @param array $params
     * @param array $data
     * @param array $expected
     *
     * @dataProvider valueDataProvider
     */
    public function testGetRawValue(array $params, array $data, array $expected)
    {
        $this->property->init(PropertyConfiguration::create($params));

        $record = new ResultRecord($data);

        $template = $this->createMock('Twig_TemplateInterface');

        $this->twig
            ->expects($this->once())
            ->method('loadTemplate')
            ->with($this->equalTo(LinkProperty::TEMPLATE))
            ->will($this->returnValue($template));

        if (!empty($data[LinkProperty::ROUTE_KEY])) {
            $this->router
                ->expects($this->once())
                ->method('generate')
                ->will($this->returnValue($data[LinkProperty::ROUTE_KEY]));
        }

        $template
            ->expects($this->once())
            ->method('render')
            ->with($this->equalTo($expected));

        $this->property->getRawValue($record);
    }

    /**
     * @return array
     */
    public function valueDataProvider()
    {
        return [
            [
                [
                    LinkProperty::ROUTE_KEY => 'route'
                ],
                [
                    'route' => 'generated'
                ],
                [
                    'url'   => 'generated',
                    'label' => null
                ]
            ],
            [
                [
                    LinkProperty::ROUTE_KEY     => 'route',
                    LinkProperty::DATA_NAME_KEY => 'data',
                ],
                [
                    'route' => 'generated'
                ],
                [
                    'url'   => 'generated',
                    'label' => null
                ]
            ],
            [
                [
                    LinkProperty::ROUTE_KEY     => 'route',
                    LinkProperty::DATA_NAME_KEY => 'title',
                    LinkProperty::NAME_KEY      => '',
                ],
                [
                    'route' => 'generated',
                    'title' => 'label'
                ],
                [
                    'url'   => 'generated',
                    'label' => 'label'
                ]
            ],
            [
                [
                    LinkProperty::ROUTE_KEY     => 'route',
                    LinkProperty::DATA_NAME_KEY => null,
                    LinkProperty::NAME_KEY      => 'title',
                ],
                [
                    'route' => 'generated',
                    'title' => 'label'
                ],
                [
                    'url'   => 'generated',
                    'label' => 'label'
                ]
            ],
        ];
    }
}
