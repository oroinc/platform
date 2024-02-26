<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Attribute\TitleTemplate;
use Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\FooBundle\Controller\TestController;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleAttributeReader;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;

class TitleAttributeReaderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private MockObject $controllerClassProvider;
    private MockObject $reader;

    protected function setUp(): void
    {
        $this->controllerClassProvider = $this->createMock(ControllerClassProvider::class);
        $this->reader = $this->createMock(AttributeReader::class);

        $this->titleAttributeReader = new TitleAttributeReader(
            $this->getTempFile('TitleAttributeReader'),
            false,
            $this->controllerClassProvider,
            $this->reader,
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
            ->method('getMethodAttribute')
            ->willReturnCallback(function (\ReflectionMethod $method, $annotationName) {
                self::assertEquals(TitleTemplate::class, $annotationName);
                if ($method->getName() === 'test1Action') {
                    return new TitleTemplate('test1 title');
                }

                return null;
            });

        $this->assertEquals('test1 title', $this->titleAttributeReader->getTitle('test1_route'));
        $this->assertNull($this->titleAttributeReader->getTitle('test2_route'));
        $this->assertNull($this->titleAttributeReader->getTitle('unknown_route'));

        // test load data from cache file
        ReflectionUtil::setPropertyValue($this->titleAttributeReader, 'config', null);
        $this->assertEquals('test1 title', $this->titleAttributeReader->getTitle('test1_route'));
        $this->assertNull($this->titleAttributeReader->getTitle('test2_route'));
        $this->assertNull($this->titleAttributeReader->getTitle('unknown_route'));
    }
}
