<?php
namespace Oro\Bundle\AsseticBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AsseticBundle\AssetsConfiguration;
use Oro\Bundle\AsseticBundle\Event\Events;

class AssetsConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var AssetsConfiguration
     */
    protected $assetsConfiguration;

    protected $defaultRawConfiguration = array(
        'css_debug_all' => true,
        'css_debug_groups' => array('foo'),
        'css' => array(
            'foo' => array(
                'foo_a.css',
                'foo_b.css',
                'foo_c.css'
            ),
            'bar' => array(
                'bar_a.css',
                'bar_b.css',
                'bar_c.css'
            ),
        )
    );

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    /**
     * @dataProvider getCssFilesDataProvider
     */
    public function testGetCss()
    {
        $assetsConfiguration = $this->createAssetsConfiguration($this->defaultRawConfiguration);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::LOAD_CSS,
                $this->isInstanceOf('Oro\Bundle\AsseticBundle\Event\LoadCssEvent')
            )
            ->will(
                $this->returnCallback(
                    function ($eventName, $event) use ($assetsConfiguration) {
                        $this->assertAttributeSame($assetsConfiguration, 'assetsConfiguration', $event);
                    }
                )
            );

        $this->assertEquals($this->defaultRawConfiguration['css'], $assetsConfiguration->getCss());
        // On second call should not dispatch event
        $this->assertEquals($this->defaultRawConfiguration['css'], $assetsConfiguration->getCss());
    }

    /**
     * @dataProvider getCssFilesDataProvider
     */
    public function testGetCssFiles($rawConfiguration, $debug, $expected)
    {
        $assetsConfiguration = $this->createAssetsConfiguration($rawConfiguration);
        $this->assertEquals($expected, $assetsConfiguration->getCssFiles($debug));
    }

    public function getCssFilesDataProvider()
    {
        return array(
            'all' => array(
                array(
                    'css' => array(
                        'foo' => array('foo_a.css', 'foo_b.css', 'foo_c.css'),
                        'bar' => array('bar_a.css', 'bar_b.css', 'bar_c.css')
                    )
                ),
                null,
                array('foo_a.css', 'foo_b.css', 'foo_c.css', 'bar_a.css', 'bar_b.css', 'bar_c.css')
            ),
            'css_debug_all' => array(
                array(
                    'css_debug_all' => true,
                    'css' => array(
                        'foo' => array('foo_a.css', 'foo_b.css', 'foo_c.css'),
                        'bar' => array('bar_a.css', 'bar_b.css', 'bar_c.css')
                    )
                ),
                true,
                array('foo_a.css', 'foo_b.css', 'foo_c.css', 'bar_a.css', 'bar_b.css', 'bar_c.css')
            ),
            'css_debug_all_invert' => array(
                array(
                    'css_debug_all' => true,
                    'css' => array(
                        'foo' => array('foo_a.css', 'foo_b.css', 'foo_c.css'),
                        'bar' => array('bar_a.css', 'bar_b.css', 'bar_c.css')
                    )
                ),
                false,
                array()
            ),
            'css_debug_groups' => array(
                array(
                    'css_debug_groups' => array('foo'),
                    'css' => array(
                        'foo' => array('foo_a.css', 'foo_b.css', 'foo_c.css'),
                        'bar' => array('bar_a.css', 'bar_b.css', 'bar_c.css')
                    )
                ),
                true,
                array('foo_a.css', 'foo_b.css', 'foo_c.css')
            ),
            'css_debug_groups_invert' => array(
                array(
                    'css_debug_groups' => array('foo'),
                    'css' => array(
                        'foo' => array('foo_a.css', 'foo_b.css', 'foo_c.css'),
                        'bar' => array('bar_a.css', 'bar_b.css', 'bar_c.css')
                    )
                ),
                false,
                array('bar_a.css', 'bar_b.css', 'bar_c.css')
            ),
        );
    }

    public function testGetCssGroups()
    {
        $assetsConfiguration = $this->createAssetsConfiguration($this->defaultRawConfiguration);
        $this->assertEquals(array('foo', 'bar'), $assetsConfiguration->getCssGroups());
    }

    protected function createAssetsConfiguration(array $rawConfiguration = array())
    {
        return new AssetsConfiguration($this->eventDispatcher, $rawConfiguration);
    }
}
