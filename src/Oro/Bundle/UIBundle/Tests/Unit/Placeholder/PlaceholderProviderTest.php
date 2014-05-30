<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Placeholder;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;

class PlaceholderProviderTest extends \PHPUnit_Framework_TestCase
{
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
                array('template' => 'template2')
            )
        )
    );

    protected function setUp()
    {
        $this->filter = $this->getMock('Oro\Bundle\UIBundle\Placeholder\Filter\PlaceholderFilterInterface');
        $this->provider = new PlaceholderProvider($this->placeholders, array($this->filter));
    }

    public function testGetPlaceholderItems()
    {
        $placeholderName = 'test_placeholder';

        $variables = array('foo' => 'bar');

        $items = $this->placeholders[$placeholderName]['items'];

        $filteredItems = $items;
        unset($filteredItems[0]);
        $filteredItems = array_values($filteredItems);

        $this->filter->expects($this->once())
            ->method('filter')
            ->with(array_values($items), $variables)
            ->will($this->returnValue($filteredItems));

        $this->assertEquals(
            $filteredItems,
            $this->provider->getPlaceholderItems($placeholderName, $variables)
        );
    }
}
