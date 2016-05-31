<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ChainExceptionTextExtractor;

class ChainExceptionTextExtractorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainExceptionTextExtractor */
    protected $chainExtractor;

    /** @var  \PHPUnit_Framework_MockObject_MockObject[] */
    protected $extractors = [];

    protected function setUp()
    {
        $this->chainExtractor = new ChainExceptionTextExtractor();

        $firstExtractor = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface')
            ->setMockClassName('FirstExceptionTextExtractor')
            ->getMock();
        $secondExtractor = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface')
            ->setMockClassName('SecondExceptionTextExtractor')
            ->getMock();

        $this->chainExtractor->addExtractor($firstExtractor);
        $this->chainExtractor->addExtractor($secondExtractor);

        $this->extractors = [$firstExtractor, $secondExtractor];
    }

    public function testGetExceptionStatusCodeByFirstExtractor()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionStatusCode')
            ->with($this->identicalTo($exception))
            ->willReturn(400);
        $this->extractors[1]->expects($this->never())
            ->method('getExceptionStatusCode');

        $this->assertEquals(400, $this->chainExtractor->getExceptionStatusCode($exception));
    }

    public function testGetExceptionStatusCodeBySecondExtractor()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionStatusCode')
            ->with($this->identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getExceptionStatusCode')
            ->with($this->identicalTo($exception))
            ->willReturn(401);

        $this->assertEquals(401, $this->chainExtractor->getExceptionStatusCode($exception));
    }

    public function testGetExceptionStatusCodeWhenFirstExtractorReturnsZero()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionStatusCode')
            ->with($this->identicalTo($exception))
            ->willReturn(0);
        $this->extractors[1]->expects($this->never())
            ->method('getExceptionStatusCode');

        $this->assertEquals(0, $this->chainExtractor->getExceptionStatusCode($exception));
    }

    public function testGetExceptionStatusCodeNone()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionStatusCode')
            ->with($this->identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getExceptionStatusCode')
            ->with($this->identicalTo($exception))
            ->willReturn(null);

        $this->assertNull($this->chainExtractor->getExceptionStatusCode($exception));
    }

    public function testGetExceptionCodeByFirstExtractor()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionCode')
            ->with($this->identicalTo($exception))
            ->willReturn('code1');
        $this->extractors[1]->expects($this->never())
            ->method('getExceptionCode');

        $this->assertEquals('code1', $this->chainExtractor->getExceptionCode($exception));
    }

    public function testGetExceptionCodeBySecondExtractor()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionCode')
            ->with($this->identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getExceptionCode')
            ->with($this->identicalTo($exception))
            ->willReturn('code2');

        $this->assertEquals('code2', $this->chainExtractor->getExceptionCode($exception));
    }

    public function testGetExceptionCodeWhenFirstExtractorReturnsEmptyString()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionCode')
            ->with($this->identicalTo($exception))
            ->willReturn('');
        $this->extractors[1]->expects($this->never())
            ->method('getExceptionCode');

        $this->assertEquals('', $this->chainExtractor->getExceptionCode($exception));
    }

    public function testGetExceptionCodeNone()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionCode')
            ->with($this->identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getExceptionCode')
            ->with($this->identicalTo($exception))
            ->willReturn(null);

        $this->assertNull($this->chainExtractor->getExceptionCode($exception));
    }

    public function testGetExceptionTypeByFirstExtractor()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionType')
            ->with($this->identicalTo($exception))
            ->willReturn('type1');
        $this->extractors[1]->expects($this->never())
            ->method('getExceptionType');

        $this->assertEquals('type1', $this->chainExtractor->getExceptionType($exception));
    }

    public function testGetExceptionTypeBySecondExtractor()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionType')
            ->with($this->identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getExceptionType')
            ->with($this->identicalTo($exception))
            ->willReturn('type2');

        $this->assertEquals('type2', $this->chainExtractor->getExceptionType($exception));
    }

    public function testGetExceptionTypeWhenFirstExtractorReturnsEmptyString()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionType')
            ->with($this->identicalTo($exception))
            ->willReturn('');
        $this->extractors[1]->expects($this->never())
            ->method('getExceptionType');

        $this->assertEquals('', $this->chainExtractor->getExceptionType($exception));
    }

    public function testGetExceptionTypeNone()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionType')
            ->with($this->identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getExceptionType')
            ->with($this->identicalTo($exception))
            ->willReturn(null);

        $this->assertNull($this->chainExtractor->getExceptionType($exception));
    }

    public function testGetExceptionTextByFirstExtractor()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionText')
            ->with($this->identicalTo($exception))
            ->willReturn('text1');
        $this->extractors[1]->expects($this->never())
            ->method('getExceptionText');

        $this->assertEquals('text1', $this->chainExtractor->getExceptionText($exception));
    }

    public function testGetExceptionTextBySecondExtractor()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionText')
            ->with($this->identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getExceptionText')
            ->with($this->identicalTo($exception))
            ->willReturn('text2');

        $this->assertEquals('text2', $this->chainExtractor->getExceptionText($exception));
    }

    public function testGetExceptionTextWhenFirstExtractorReturnsEmptyString()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionText')
            ->with($this->identicalTo($exception))
            ->willReturn('');
        $this->extractors[1]->expects($this->never())
            ->method('getExceptionText');

        $this->assertEquals('', $this->chainExtractor->getExceptionText($exception));
    }

    public function testGetExceptionTextNone()
    {
        $exception = new \Exception();

        $this->extractors[0]->expects($this->once())
            ->method('getExceptionText')
            ->with($this->identicalTo($exception))
            ->willReturn(null);
        $this->extractors[1]->expects($this->once())
            ->method('getExceptionText')
            ->with($this->identicalTo($exception))
            ->willReturn(null);

        $this->assertNull($this->chainExtractor->getExceptionText($exception));
    }
}
