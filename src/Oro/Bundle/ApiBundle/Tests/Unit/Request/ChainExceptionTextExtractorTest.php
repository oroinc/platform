<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ChainExceptionTextExtractor;
use Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface;

class ChainExceptionTextExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainExceptionTextExtractor */
    private $chainExtractor;

    /** @var \PHPUnit\Framework\MockObject\MockObject[] */
    private $extractors = [];

    protected function setUp()
    {
        $this->chainExtractor = new ChainExceptionTextExtractor();

        $firstExtractor = $this->getMockBuilder(ExceptionTextExtractorInterface::class)
            ->setMockClassName('FirstExceptionTextExtractor')
            ->getMock();
        $secondExtractor = $this->getMockBuilder(ExceptionTextExtractorInterface::class)
            ->setMockClassName('SecondExceptionTextExtractor')
            ->getMock();

        $this->chainExtractor->addExtractor($firstExtractor);
        $this->chainExtractor->addExtractor($secondExtractor);

        $this->extractors = [$firstExtractor, $secondExtractor];
    }

    public function testGetExceptionStatusCodeByFirstExtractor()
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

    public function testGetExceptionStatusCodeBySecondExtractor()
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

    public function testGetExceptionStatusCodeWhenFirstExtractorReturnsZero()
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

    public function testGetExceptionStatusCodeNone()
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

    public function testGetExceptionCodeByFirstExtractor()
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

    public function testGetExceptionCodeBySecondExtractor()
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

    public function testGetExceptionCodeWhenFirstExtractorReturnsEmptyString()
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

    public function testGetExceptionCodeNone()
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

    public function testGetExceptionTypeByFirstExtractor()
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

    public function testGetExceptionTypeBySecondExtractor()
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

    public function testGetExceptionTypeWhenFirstExtractorReturnsEmptyString()
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

    public function testGetExceptionTypeNone()
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

    public function testGetExceptionTextByFirstExtractor()
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

    public function testGetExceptionTextBySecondExtractor()
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

    public function testGetExceptionTextWhenFirstExtractorReturnsEmptyString()
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

    public function testGetExceptionTextNone()
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
