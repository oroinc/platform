<?php
namespace Oro\Bundle\AsseticBundle\Tests\Unit\Twig;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\FileAsset;
use Oro\Bundle\AsseticBundle\Twig\DebugAsseticNode;

class DebugAsseticNodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $compiler;

    protected function setUp()
    {
        $this->compiler = $this->getMockBuilder('\Twig_Compiler')
            ->disableOriginalConstructor()
            ->setMethods(array())
            ->getMock();
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(DebugAsseticNode $node, $calls)
    {
        $callIndex = 0;

        foreach ($calls as $methodAndArguments) {
            list($method, $arguments) = $methodAndArguments;

            $mocker = $this->compiler->expects($this->at($callIndex++))->method($method);
            $mocker = call_user_func_array(array($mocker, 'with'), $arguments);
            $mocker->will($this->returnSelf());
        }

        $node->compile($this->compiler);
    }

    public function compileDataProvider()
    {
        return array(
            array(
                'node' => $this->createDebugAsseticNode(
                    new AssetCollection(array(new FileAsset('first.scss.css'), new FileAsset('second.scss.css'))),
                    array('first.scss.css', 'second.scss.css'),
                    'test'
                ),
                'compilerCalls' => array(
                    array('addDebugInfo', array($this->isInstanceOf('Oro\Bundle\AsseticBundle\Twig\DebugAsseticNode'))),
                    // start first.scss.css
                    array('write', array("// asset \"test_0\"\n")),
                    array('write', array('$context[')),
                    array('repr', array('asset_url')),
                    array('raw', array('] = ')),
                    array('raw', array('$this->env->getExtension(\'asset\')->getAssetUrl(')),
                    array('repr', array('first.scss.css')),
                    array('raw', array(')')),
                    array('raw', array(";\n")),
                    array('subcompile', array($this->isInstanceOf('Twig_Node'))),
                    // start second.scss.css
                    array('write', array("// asset \"test_1\"\n")),
                    array('write', array('$context[')),
                    array('repr', array('asset_url')),
                    array('raw', array('] = ')),
                    array('raw', array('$this->env->getExtension(\'asset\')->getAssetUrl(')),
                    array('repr', array('second.scss.css')),
                    array('raw', array(')')),
                    array('raw', array(";\n")),
                    array('subcompile', array($this->isInstanceOf('Twig_Node'))),
                    // end second.scss.css
                    array('write', array('unset($context[')),
                    array('repr', array('asset_url')),
                    array('raw', array("]);\n")),
                )
            )
        );
    }

    protected function createDebugAsseticNode(AssetInterface $asset, array $inputs, $name)
    {
        $body = new \Twig_Node();
        $filters = array();
        $attributes = array();

        return new DebugAsseticNode($asset, $body, $inputs, $filters, $name, $attributes);
    }
}
