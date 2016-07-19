<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Model;

use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

class ThemeImageTypeDimensionTest extends \PHPUnit_Framework_TestCase
{
    const NAME = 'dim';
    const WIDTH = 100;
    const HEIGHT = 200;

    /**
     * @var ThemeImageTypeDimension
     */
    protected $imageTypeDimension;

    public function setUp()
    {
        $this->imageTypeDimension = new ThemeImageTypeDimension(self::NAME, self::WIDTH, self::HEIGHT);
    }

    public function testAccessors()
    {
        $this->assertEquals(self::NAME, $this->imageTypeDimension->getName());
        $this->assertEquals(self::WIDTH, $this->imageTypeDimension->getWidth());
        $this->assertEquals(self::HEIGHT, $this->imageTypeDimension->getHeight());
    }
}
