<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Label;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $error = Error::create('title', 'detail');
        $this->assertEquals('title', $error->getTitle());
        $this->assertEquals('detail', $error->getDetail());
    }

    public function testCreateValidationError()
    {
        $error = Error::createValidationError('title', 'detail');
        $this->assertEquals(400, $error->getStatusCode());
        $this->assertEquals('title', $error->getTitle());
        $this->assertEquals('detail', $error->getDetail());
    }

    public function testCreateByException()
    {
        $exception = new \Exception();
        $error = Error::createByException($exception);
        $this->assertSame($exception, $error->getInnerException());
    }

    public function testStatusCode()
    {
        $error = new Error();
        $this->assertNull($error->getStatusCode());

        $this->assertSame($error, $error->setStatusCode(400));
        $this->assertEquals(400, $error->getStatusCode());
    }

    public function testCode()
    {
        $error = new Error();
        $this->assertNull($error->getCode());

        $this->assertSame($error, $error->setCode('test'));
        $this->assertEquals('test', $error->getCode());
    }

    public function testTitle()
    {
        $error = new Error();
        $this->assertNull($error->getTitle());

        $this->assertSame($error, $error->setTitle('test'));
        $this->assertEquals('test', $error->getTitle());
    }

    public function testDetail()
    {
        $error = new Error();
        $this->assertNull($error->getDetail());

        $this->assertSame($error, $error->setDetail('test'));
        $this->assertEquals('test', $error->getDetail());
    }

    public function testSource()
    {
        $error = new Error();
        $this->assertNull($error->getSource());

        $source = new ErrorSource();
        $this->assertSame($error, $error->setSource($source));
        $this->assertSame($source, $error->getSource());

        $error->setSource(null);
        $this->assertNull($error->getSource());
    }

    public function testInnerException()
    {
        $error = new Error();
        $this->assertNull($error->getInnerException());

        $exception = new \Exception();
        $this->assertSame($error, $error->setInnerException($exception));
        $this->assertSame($exception, $error->getInnerException());

        $error->setInnerException(null);
        $this->assertNull($error->getInnerException());
    }

    public function testTrans()
    {
        $error = new Error();
        $error->setTitle(new Label('title'));
        $error->setDetail(new Label('detail'));

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->at(0))
            ->method('trans')
            ->with('title')
            ->willReturn('translated_title');
        $translator->expects($this->at(1))
            ->method('trans')
            ->with('detail')
            ->willReturn('translated_detail');

        $error->trans($translator);
        $this->assertEquals('translated_title', $error->getTitle());
        $this->assertEquals('translated_detail', $error->getDetail());
    }

    public function testTransWhenNothingToTranslate()
    {
        $error = new Error();

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->never())
            ->method('trans');

        $error->trans($translator);
        $this->assertNull($error->getTitle());
        $this->assertNull($error->getDetail());
    }
}
