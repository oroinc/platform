<?php
namespace Oro\Bundle\AsseticBundle\Tests\Unit\Twig;

use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;

use Oro\Bundle\AsseticBundle\Twig\DebugAsseticNode;

class DebugAsseticNodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DebugAsseticNode
     */
    private $node;

    /**
     * @var AssetInterface
     */
    protected $asset;

    public function setUp()
    {
        $asset = new FileAsset('first.less.css');
        $this->asset = new AssetCollection(array($asset));
        $this->node = new DebugAsseticNode(
            $this->asset,
            new \Twig_Node(),
            array('first.less.css'),
            array(),
            'test',
            array(),
            10,
            'oro_css'
        );
    }

    public function testCompile()
    {
        $compiler = $this->assetsFactory = $this->getMockBuilder('\Twig_Compiler')
            ->disableOriginalConstructor()
            ->getMock();

        $compiler->expects($this->any())
            ->method('write')
            ->will($this->returnValue($compiler));

        $compiler->expects($this->any())
            ->method('repr')
            ->will($this->returnValue($compiler));

        $compiler->expects($this->any())
            ->method('raw')
            ->will($this->returnValue($compiler));

        $this->node->compile($compiler);
    }
}
