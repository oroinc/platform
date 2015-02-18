<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Loader\PhpFileLoader;

use Oro\Component\Layout\Extension\PreloadedExtension;
use Oro\Component\Layout\Tests\Unit\DeferredLayoutManipulatorTestCase;

class PhpFileLoaderTest extends DeferredLayoutManipulatorTestCase
{
    /** @var PhpFileLoader */
    protected $loader;

    protected function setUp()
    {
        parent::setUp();
        $this->loader = new PhpFileLoader();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->loader);
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param mixed $resource
     * @param bool  $expectedResult
     */
    public function testSupports($resource, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->loader->supports($resource));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'should support php resource'                          => [
                '$resource'       => 'test.php',
                '$expectedResult' => true
            ],
            'should not support zip resource'                      => [
                '$resource'       => 'test.zip',
                '$expectedResult' => false
            ],
            'impossible to check resource type, should no support' => [
                '$resource'       => new \stdClass(),
                '$expectedResult' => false
            ]
        ];
    }

    public function testLoad()
    {
        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../../Stubs/Updates/layout_update.php';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $update = $this->loader->load($path);
        $this->layoutManipulator->add('root', null, 'root');
        $this->registry->addExtension(new PreloadedExtension([], [], ['root' => [$update]]));

        $view = $this->getLayoutView();
        $this->assertBlockView(
            [ // root
              'vars'     => ['id' => 'root'],
              'children' => [
                  [ // header
                    'vars'     => ['id' => 'header'],
                    'children' => [
                        [ // logo
                          'vars' => ['id' => 'logo', 'title' => 'test']
                        ]
                    ]
                  ]
              ]
            ],
            $view
        );
    }
}
