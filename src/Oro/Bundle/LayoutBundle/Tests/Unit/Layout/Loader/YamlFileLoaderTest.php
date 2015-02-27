<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\LayoutBundle\Exception\SyntaxException;
use Oro\Bundle\LayoutBundle\Layout\Loader\FileResource;
use Oro\Bundle\LayoutBundle\Layout\Loader\YamlFileLoader;
use Oro\Bundle\LayoutBundle\Layout\Generator\GeneratorData;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;

class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase
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
            'should support yml file resource'       => [
                '$resource'       => new FileResource('test.yml'),
                '$expectedResult' => true
            ],
            'should not support php resource'        => [
                '$resource'       => new FileResource('test.php'),
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

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../../Stubs/Updates/layout_update.yml';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $update = $loader->load(new FileResource($path));
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $update);
    }

    public function testLoadInProductionMode()
    {
        $fs  = new Filesystem();
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . time();

        $generator = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator, false, $dir);

        $generator->expects($this->once())->method('generate')->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../../Stubs/Updates/layout_update2.yml';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $update = $loader->load(new FileResource($path));
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $update);
        $this->assertCount(1, $files = iterator_to_array(new \FilesystemIterator($dir)));

        $fs->remove($dir);
    }

    public function testTakesIntoAccountRoute()
    {
        $generator = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator);

        $path     = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../../Stubs/Updates/layout_update3.yml';
        $path     = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $resource = new FileResource($path);

        $generator->expects($this->once())->method('generate')
            ->willReturnCallback(
                function ($className, $data, ConditionCollection $collection) use ($resource) {
                    $this->assertSame($resource->getConditions(), $collection);

                    return $this->buildClass($className, $data);
                }
            );

        $loader->load($resource);
    }

    public function testPassesParsedYamlContentToGenerator()
    {
        $generator = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../../Stubs/Updates/layout_update4.yml';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $generator->expects($this->once())->method('generate')
            ->willReturnCallback(
                function ($className, GeneratorData $data, ConditionCollection $collection) use ($path) {
                    $this->assertNotEmpty($data);
                    $this->assertSame(
                        ['actions' => [['@add' => ['id' => 'root', 'parent' => null]]]],
                        $data->getSource()
                    );
                    $this->assertSame($path, $data->getFilename());

                    return $this->buildClass($className);
                }
            );

        $loader->load(new FileResource($path));
    }

    public function testProcessSyntaxExceptions()
    {
        $generator = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator);

        $exception = new SyntaxException(
            'action name should start with "@" symbol, current name "add"',
            ['add' => ['id' => 'myId', 'parentId' => 'myParentId']],
            'actions.0'
        );
        $generator->expects($this->once())->method('generate')->willThrowException($exception);

        $message = <<<MESSAGE
Syntax error: action name should start with "@" symbol, current name "add" at "actions.0"
add:
    id: myId
    parentId: myParentId


Filename: path/to/my/file.yml
MESSAGE;
        $this->setExpectedException('\RuntimeException', $message);

        $update = $loader->load(new FileResource('path/to/my/file.yml'));
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $update);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function buildClass($className)
    {
        return <<<CLASS
<?php
    use \Oro\Component\Layout\LayoutManipulatorInterface;
    use \Oro\Component\Layout\LayoutItemInterface;

    class $className implements \Oro\Component\Layout\LayoutUpdateInterface
    {
        public function updateLayout(LayoutManipulatorInterface \$layoutManipulator, LayoutItemInterface \$item)
        {
        }
    }
CLASS;
    }

    /**
     * @param null|LayoutUpdateGeneratorInterface $generator
     * @param bool                                $debug
     * @param bool                                $cache
     *
     * @return YamlFileLoader
     */
    protected function getLoader($generator = null, $debug = false, $cache = false)
    {
        $generator = null === $generator
            ? $this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface')
            : $generator;

        return new YamlFileLoader($generator, $debug, $cache);
    }
}
