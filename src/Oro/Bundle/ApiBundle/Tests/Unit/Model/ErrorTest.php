<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Label;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    public function testStatusCode()
    {
        $error = new Error();
        $this->assertNull($error->getStatusCode());

        $error->setStatusCode(400);
        $this->assertEquals(400, $error->getStatusCode());
    }

    public function testCode()
    {
        $error = new Error();
        $this->assertNull($error->getCode());

        $error->setCode('test');
        $this->assertEquals('test', $error->getCode());
    }

    public function testTitle()
    {
        $error = new Error();
        $this->assertNull($error->getTitle());

        $error->setTitle('test');
        $this->assertEquals('test', $error->getTitle());
    }

    public function testDetail()
    {
        $error = new Error();
        $this->assertNull($error->getDetail());

        $error->setDetail('test');
        $this->assertEquals('test', $error->getDetail());
    }

    public function testSource()
    {
        $error = new Error();
        $this->assertNull($error->getSource());

        $source = new ErrorSource();
        $error->setSource($source);
        $this->assertSame($source, $error->getSource());

        $error->setSource(null);
        $this->assertNull($error->getSource());
    }

    public function testInnerException()
    {
        $error = new Error();
        $this->assertNull($error->getInnerException());

        $exception = new \Exception();
        $error->setInnerException($exception);
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
