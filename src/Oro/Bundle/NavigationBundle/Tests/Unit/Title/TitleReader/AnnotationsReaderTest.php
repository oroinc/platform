<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Doctrine\Common\Annotations\Reader;
use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;
use Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\FooBundle\Controller\TestController;
use Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Oro\Component\PhpUtils\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;

class AnnotationsReaderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var ControllerClassProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $controllerClassProvider;

    /** @var Reader|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var AnnotationsReader */
    private $annotationReader;

    /** @var string */
    private $cacheFile;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('AnnotationsReader');

        $this->controllerClassProvider = $this->createMock(ControllerClassProvider::class);
        $this->reader = $this->createMock(Reader::class);

        $this->annotationReader = new AnnotationsReader(
            $this->cacheFile,
            false,
            $this->controllerClassProvider,
            $this->reader
        );
    }

    public function testGetTitle()
    {
        $this->controllerClassProvider->expects(self::once())
            ->method('getControllers')
            ->willReturn([
                'test1_route' => [TestController::class, 'test1Action'],
                'test2_route' => [TestController::class, 'test2Action']
            ]);
        $this->reader->expects(self::exactly(2))
            ->method('getMethodAnnotation')
            ->willReturnCallback(function (\ReflectionMethod $method, $annotationName) {
                self::assertEquals(TitleTemplate::class, $annotationName);
                if ($method->getName() === 'test1Action') {
                    return new TitleTemplate(['value' => 'test1 title']);
                }

                return null;
            });

        $this->assertEquals('test1 title', $this->annotationReader->getTitle('test1_route'));
        $this->assertNull($this->annotationReader->getTitle('test2_route'));
        $this->assertNull($this->annotationReader->getTitle('unknown_route'));

        // test load data from cache file
        $refl = ReflectionUtil::getProperty(new \ReflectionClass($this->annotationReader), 'config');
        $refl->setAccessible(true);
        $refl->setValue($this->annotationReader, null);
        $this->assertEquals('test1 title', $this->annotationReader->getTitle('test1_route'));
        $this->assertNull($this->annotationReader->getTitle('test2_route'));
        $this->assertNull($this->annotationReader->getTitle('unknown_route'));
    }
}
