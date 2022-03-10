<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Request\ParamConverter;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\DriverException;
use Oro\Bundle\EntityBundle\Request\ParamConverter\DoctrineParamConverterDecorator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DoctrineParamConverterDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParamConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paramConverter;

    /** @var DoctrineParamConverterDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->paramConverter = $this->createMock(ParamConverterInterface::class);

        $this->decorator = new DoctrineParamConverterDecorator($this->paramConverter);
    }

    public function testSupports(): void
    {
        $configuration = new ParamConverter([]);

        $this->paramConverter->expects($this->once())
            ->method('supports')
            ->with($configuration)
            ->willReturn(true);

        $this->assertTrue($this->decorator->supports($configuration));
    }

    public function testApply(): void
    {
        $request = new Request(['param1' => 'value1']);
        $configuration = new ParamConverter([]);

        $expected = new \stdClass();

        $this->paramConverter->expects($this->once())
            ->method('apply')
            ->with($request, $configuration)
            ->willReturn($expected);

        $this->assertSame($expected, $this->decorator->apply($request, $configuration));
    }

    public function testApplyWithOutOfRangeException(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('stdClass object not found.');

        $request = new Request(['param1' => 'value1']);
        $configuration = new ParamConverter(['class' => \stdClass::class]);

        $exception = new \PDOException();
        $exception->errorInfo[0] = '22003';

        $this->paramConverter->expects($this->once())
            ->method('apply')
            ->with($request, $configuration)
            ->willThrowException(
                new DriverException('out of range', new PDOException($exception))
            );

        $this->decorator->apply($request, $configuration);
    }

    public function testApplyWithOtherException(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('out of range');

        $request = new Request(['param1' => 'value1']);
        $configuration = new ParamConverter([]);

        $this->paramConverter->expects($this->once())
            ->method('apply')
            ->with($request, $configuration)
            ->willThrowException(
                new DriverException('not out of range', new PDOException(new \PDOException()))
            );

        $this->decorator->apply($request, $configuration);
    }
}
