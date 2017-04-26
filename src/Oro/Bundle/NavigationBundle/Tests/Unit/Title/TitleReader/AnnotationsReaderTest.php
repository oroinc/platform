<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Doctrine\Common\Annotations\Reader;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;
use Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader;

class AnnotationsReaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    private $request;

    /** @var Reader|\PHPUnit_Framework_MockObject_MockObject */
    private $reader;

    /** @var AnnotationsReader */
    private $annotationReader;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->reader = $this->getMockBuilder(Reader::class)->disableOriginalConstructor()->getMock();

        $requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->annotationReader = new AnnotationsReader($requestStack, $this->reader);
    }

    public function testGetTitle()
    {
        $route = 'test_route';

        $annotation = $this->getMockBuilder(TitleTemplate::class)->disableOriginalConstructor()->getMock();
        $annotation->expects($this->once())
            ->method('getValue')
            ->willReturn('Title Template');

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('_controller')
            ->willReturn(AnnotationsReaderTest::class . '::testGetTitle');

        $reflectionMethod = new \ReflectionMethod(AnnotationsReaderTest::class, 'testGetTitle');

        $this->reader
            ->expects($this->once())
            ->method('getMethodAnnotation')
            ->with($reflectionMethod, TitleTemplate::class)
            ->willReturn($annotation);

        $this->assertEquals('Title Template', $this->annotationReader->getTitle($route));
    }

    public function testGetTitleEmpty()
    {
        $route = 'test_route';

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('_controller')
            ->willReturn(AnnotationsReaderTest::class . '::testGetTitleEmpty');

        $reflectionMethod = new \ReflectionMethod(AnnotationsReaderTest::class, 'testGetTitleEmpty');

        $this->reader
            ->expects($this->once())
            ->method('getMethodAnnotation')
            ->with($reflectionMethod, TitleTemplate::class)
            ->willReturn(null);

        $this->assertNull($this->annotationReader->getTitle($route));
    }

    public function testGetTitleControllerIsEmpty()
    {
        $route = 'test_route';

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('_controller')
            ->willReturn(null);

        $this->reader
            ->expects($this->never())
            ->method('getMethodAnnotation');

        $this->assertNull($this->annotationReader->getTitle($route));
    }
}
