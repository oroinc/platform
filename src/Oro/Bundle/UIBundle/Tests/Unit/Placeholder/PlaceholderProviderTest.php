<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Placeholder;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;

class PlaceholderProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $filter;

    /**
     * @var PlaceholderProvider
     */
    protected $provider;

    protected $placeholders = array(
        'test_placeholder' => array(
            'items' => array(
                array('template' => 'template1'),
                array('template' => 'template2'),
                array('template' => 'template3'),
                array('template' => 'template4'),
            )
        )
    );

    protected function setUp()
    {
        $this->resolver = $this->getMock('Oro\Component\Config\Resolver\ResolverInterface');
        $this->filter   = $this->getMock('Oro\Bundle\UIBundle\Placeholder\Filter\PlaceholderFilterInterface');
        $this->provider = new PlaceholderProvider($this->placeholders, $this->resolver, array($this->filter));
    }

    public function testGetPlaceholderItems()
    {
        $placeholderName = 'test_placeholder';

        $variables = array('foo' => 'bar');

        $items = $this->placeholders[$placeholderName]['items'];

        $this->resolver->expects($this->at(0))
            ->method('resolve')
            ->with($items[0], $variables)
            ->will($this->returnValue($items[0]));
        $this->resolver->expects($this->at(1))
            ->method('resolve')
            ->with($items[1], $variables)
            ->will($this->returnValue($items[1]));
        $item3 = $items[2];
        $item3['applicable'] = true;
        $this->resolver->expects($this->at(2))
            ->method('resolve')
            ->with($items[2], $variables)
            ->will($this->returnValue($item3));
        $item = $items[3];
        $item['applicable'] = false;
        $this->resolver->expects($this->at(3))
            ->method('resolve')
            ->with($items[3], $variables)
            ->will($this->returnValue($item));

        $itemsToFilter = array($items[0], $items[1], $item3);
        $filteredItems = array($items[1]);
        $this->filter->expects($this->once())
            ->method('filter')
            ->with($itemsToFilter, $variables)
            ->will($this->returnValue($filteredItems));

        $this->assertEquals(
            $filteredItems,
            $this->provider->getPlaceholderItems($placeholderName, $variables)
        );
    }
}
