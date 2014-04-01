<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Placeholder;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;

class PlaceholderProviderTest extends \PHPUnit_Framework_TestCase
{
    const PLACEHOLDER_NAME = 'placeholder_name';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $filter;

    /**
     * @var PlaceholderProvider
     */
    protected $provider;

    protected $placeholders = array(
        self::PLACEHOLDER_NAME => array(
            'items' => array(
                array('template' => 'foo')
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
        $variables = array('foo' => 'bar');

        $expectedResult = $this->placeholders[self::PLACEHOLDER_NAME]['items'];
        $expectedResult['items'][0]['template'] = 'filtered';

        $this->provider->getPlaceholderItems(self::PLACEHOLDER_NAME, $variables);
        $this->filter->expects($this->once())
            ->method('filter')
            ->with($this->placeholders[self::PLACEHOLDER_NAME]['items'])
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            $this->provider->getPlaceholderItems(self::PLACEHOLDER_NAME, $variables)
        );
    }
}
