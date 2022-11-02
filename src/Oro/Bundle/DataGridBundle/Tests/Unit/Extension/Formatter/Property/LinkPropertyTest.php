<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\LinkProperty;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;
use Oro\Bundle\UIBundle\Twig\Environment;
use Symfony\Component\Routing\RouterInterface;

class LinkPropertyTest extends \PHPUnit\Framework\TestCase
{
    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var LinkProperty */
    private $property;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->property = new LinkProperty($this->router, $this->twig);
    }

    /**
     * @dataProvider valueDataProvider
     */
    public function testGetRawValue(array $params, array $data, array $expected)
    {
        $this->property->init(PropertyConfiguration::create($params));

        $record = new ResultRecord($data);

        if (!empty($data[LinkProperty::ROUTE_KEY])) {
            $this->router->expects($this->once())
                ->method('generate')
                ->willReturn($data[LinkProperty::ROUTE_KEY]);
        }

        $this->twig->expects($this->once())
            ->method('render')
            ->with(LinkProperty::TEMPLATE, $expected);

        $this->property->getRawValue($record);
    }

    public function valueDataProvider(): array
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
