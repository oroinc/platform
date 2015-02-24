<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\LayoutBundle\Layout\Loader\FileResource;
use Oro\Bundle\LayoutBundle\Layout\Loader\PhpFileLoader;
use Oro\Bundle\LayoutBundle\Layout\Generator\GeneratorData;
use Oro\Bundle\LayoutBundle\Layout\Loader\RouteFileResource;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;

class PhpFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider supportsDataProvider
     *
     * @param mixed $resource
     * @param bool  $expectedResult
     */
    public function testSupports($resource, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->getLoader()->supports($resource));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'should support php file resource'       => [
                '$resource'       => new FileResource('test.php'),
                '$expectedResult' => true
            ],
            'should support php route file resource' => [
                '$resource'       => new RouteFileResource('test.php', uniqid('test_route')),
                '$expectedResult' => true
            ],
            'should not support yml resource'        => [
                '$resource'       => new FileResource('test.yml'),
                '$expectedResult' => false
            ],
            'should not support zip resource'        => [
                '$resource'       => new FileResource('test.zip'),
                '$expectedResult' => false
            ]
        ];
    }

    public function testLoadInDebugMode()
    {
        $generator = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator);

        $generator->expects($this->once())->method('generate')->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../../Stubs/Updates/layout_update.php';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $update = $loader->load(new FileResource($path));
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $update);

        $this->assertSame($update, $loader->load(new FileResource($path)), 'Should evaluate and instantiate once');
    }

    public function testLoadInProductionMode()
    {
        $fs  = new Filesystem();
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . time();

        $generator = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator, false, $dir);

        $generator->expects($this->once())->method('generate')->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../../Stubs/Updates/layout_update2.php';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $update = $loader->load(new FileResource($path));
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $update);

        $this->assertSame($update, $loader->load(new FileResource($path)), 'Should evaluate and instantiate once');
        $this->assertCount(1, $files = iterator_to_array(new \FilesystemIterator($dir)));

        $fs->remove($dir);
    }

    public function testTakesIntoAccountRoute()
    {
        $generator = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator);

        $generator->expects($this->once())->method('generate')
            ->willReturnCallback(
                function ($className, $data, ConditionCollection $collection) {
                    $this->assertNotEmpty($collection);
                    $this->assertContainsOnlyInstancesOf(
                        '\Oro\Bundle\LayoutBundle\Layout\Generator\Condition\SimpleContextValueComparisonCondition',
                        $collection
                    );

                    return $this->buildClass($className, $data);
                }
            );
        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../../Stubs/Updates/layout_update3.php';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $loader->load(new RouteFileResource($path, uniqid('route')));
    }

    /**
     * @param string        $className
     * @param GeneratorData $data
     *
     * @return string
     */
    public function buildClass($className, GeneratorData $data)
    {
        $data = str_replace(['<?php', '<?', '?>'], '', $data->getSource());

        return <<<CLASS
<?php
    use \Oro\Component\Layout\LayoutManipulatorInterface;
    use \Oro\Component\Layout\LayoutItemInterface;

    class $className implements \Oro\Component\Layout\LayoutUpdateInterface
    {
        public function updateLayout(LayoutManipulatorInterface \$layoutManipulator, LayoutItemInterface \$item)
        {
            $data
        }
    }
CLASS;
    }

    /**
     * @param null|LayoutUpdateGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject $generator
     * @param bool                                                                         $debug
     * @param bool                                                                         $cache
     *
     * @return PhpFileLoader
     */
    protected function getLoader($generator = null, $debug = true, $cache = false)
    {
        $generator = null === $generator
            ? $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface')
            : $generator;

        return new PhpFileLoader($generator, $debug, $cache);
    }
}
