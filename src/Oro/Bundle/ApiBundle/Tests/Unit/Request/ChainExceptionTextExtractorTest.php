<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ChainExceptionTextExtractor;
use Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChainExceptionTextExtractorTest extends TestCase
{
    private ChainExceptionTextExtractor $chainExtractor;
    /** @var ExceptionTextExtractorInterface[]&MockObject[] */
    private array $extractors;

    #[\Override]
    protected function setUp(): void
    {
        $firstExtractor = $this->createMock(ExceptionTextExtractorInterface::class);
        $secondExtractor = $this->createMock(ExceptionTextExtractorInterface::class);

        $this->extractors = [$firstExtractor, $secondExtractor];
        $this->chainExtractor = new ChainExceptionTextExtractor($this->extractors);
    }

    public function testGetExceptionStatusCodeByFirstExtractor(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn(400);
        $this->extractors[1]->expects(self::never())
            ->method('getExceptionStatusCode');

        self::assertEquals(400, $this->chainExtractor->getExceptionStatusCode($exception));
    }

    public function testGetExceptionStatusCodeBySecondExtractor(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn(401);

        self::assertEquals(401, $this->chainExtractor->getExceptionStatusCode($exception));
    }

    public function testGetExceptionStatusCodeWhenFirstExtractorReturnsZero(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn(0);
        $this->extractors[1]->expects(self::never())
            ->method('getExceptionStatusCode');

        self::assertEquals(0, $this->chainExtractor->getExceptionStatusCode($exception));
    }

    public function testGetExceptionStatusCodeNone(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn(null);

        self::assertNull($this->chainExtractor->getExceptionStatusCode($exception));
    }

    public function testGetExceptionCodeByFirstExtractor(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn('code1');
        $this->extractors[1]->expects(self::never())
            ->method('getExceptionCode');

        self::assertEquals('code1', $this->chainExtractor->getExceptionCode($exception));
    }

    public function testGetExceptionCodeBySecondExtractor(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn('code2');

        self::assertEquals('code2', $this->chainExtractor->getExceptionCode($exception));
    }

    public function testGetExceptionCodeWhenFirstExtractorReturnsEmptyString(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn('');
        $this->extractors[1]->expects(self::never())
            ->method('getExceptionCode');

        self::assertEquals('', $this->chainExtractor->getExceptionCode($exception));
    }

    public function testGetExceptionCodeNone(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn(null);

        self::assertNull($this->chainExtractor->getExceptionCode($exception));
    }

    public function testGetExceptionTypeByFirstExtractor(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionType')
            ->with(self::identicalTo($exception))
            ->willReturn('type1');
        $this->extractors[1]->expects(self::never())
            ->method('getExceptionType');

        self::assertEquals('type1', $this->chainExtractor->getExceptionType($exception));
    }

    public function testGetExceptionTypeBySecondExtractor(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionType')
            ->with(self::identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getExceptionType')
            ->with(self::identicalTo($exception))
            ->willReturn('type2');

        self::assertEquals('type2', $this->chainExtractor->getExceptionType($exception));
    }

    public function testGetExceptionTypeWhenFirstExtractorReturnsEmptyString(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionType')
            ->with(self::identicalTo($exception))
            ->willReturn('');
        $this->extractors[1]->expects(self::never())
            ->method('getExceptionType');

        self::assertEquals('', $this->chainExtractor->getExceptionType($exception));
    }

    public function testGetExceptionTypeNone(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionType')
            ->with(self::identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getExceptionType')
            ->with(self::identicalTo($exception))
            ->willReturn(null);

        self::assertNull($this->chainExtractor->getExceptionType($exception));
    }

    public function testGetExceptionTextByFirstExtractor(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionText')
            ->with(self::identicalTo($exception))
            ->willReturn('text1');
        $this->extractors[1]->expects(self::never())
            ->method('getExceptionText');

        self::assertEquals('text1', $this->chainExtractor->getExceptionText($exception));
    }

    public function testGetExceptionTextBySecondExtractor(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionText')
            ->with(self::identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getExceptionText')
            ->with(self::identicalTo($exception))
            ->willReturn('text2');

        self::assertEquals('text2', $this->chainExtractor->getExceptionText($exception));
    }

    public function testGetExceptionTextWhenFirstExtractorReturnsEmptyString(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionText')
            ->with(self::identicalTo($exception))
            ->willReturn('');
        $this->extractors[1]->expects(self::never())
            ->method('getExceptionText');

        self::assertEquals('', $this->chainExtractor->getExceptionText($exception));
    }

    public function testGetExceptionTextNone(): void
    {
        $exception = new \Exception();

        $this->extractors[0]->expects(self::once())
            ->method('getExceptionText')
            ->with(self::identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects(self::once())
            ->method('getExceptionText')
            ->with(self::identicalTo($exception))
            ->willReturn(null);

        self::assertNull($this->chainExtractor->getExceptionText($exception));
    }
}
