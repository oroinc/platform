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
                'item1' => array('template' => 'template1'),
                'item2' => array('template' => 'template2')
            )
        ),
        'test_placeholder_with_blocks' => array(
            'items' => array(
                'item1' => array('template' => 'template1', 'block' => 'block1'),
                'item2' => array('template' => 'template2', 'block' => 'block2')
            ),
            'blocks' => array(
                'block1' => array('label' => 'Block 1'),
                'block2' => array('label' => 'Block 2'),
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
        unset($filteredItems['item1']);
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

    public function testGetPlaceholderBlocks()
    {
        $placeholderName = 'test_placeholder_with_blocks';

        $variables = array('foo' => 'bar');

        $blocks = $this->placeholders[$placeholderName]['blocks'];

        $filteredBlocks = $blocks;
        unset($filteredBlocks['block1']);

        $this->filter->expects($this->once())
            ->method('filter')
            ->with($blocks, $variables)
            ->will($this->returnValue($filteredBlocks));

        $this->assertEquals(
            $filteredBlocks,
            $this->provider->getPlaceholderBlocks($placeholderName, $variables)
        );
    }
}
