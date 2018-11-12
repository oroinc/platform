<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Label;
use Symfony\Component\Translation\TranslatorInterface;

class ErrorTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $error = Error::create('title', 'detail');
        self::assertEquals('title', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateValidationError()
    {
        $error = Error::createValidationError('title', 'detail');
        self::assertEquals(400, $error->getStatusCode());
        self::assertEquals('title', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateConflictValidationError()
    {
        $error = Error::createConflictValidationError('detail');
        self::assertEquals(409, $error->getStatusCode());
        self::assertEquals('conflict constraint', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateByException()
    {
        $exception = new \Exception();
        $error = Error::createByException($exception);
        self::assertSame($exception, $error->getInnerException());
    }

    public function testStatusCode()
    {
        $error = new Error();
        self::assertNull($error->getStatusCode());

        self::assertSame($error, $error->setStatusCode(400));
        self::assertEquals(400, $error->getStatusCode());
    }

    public function testCode()
    {
        $error = new Error();
        self::assertNull($error->getCode());

        self::assertSame($error, $error->setCode('test'));
        self::assertEquals('test', $error->getCode());
    }

    public function testTitle()
    {
        $error = new Error();
        self::assertNull($error->getTitle());

        self::assertSame($error, $error->setTitle('test'));
        self::assertEquals('test', $error->getTitle());
    }

    public function testDetail()
    {
        $error = new Error();
        self::assertNull($error->getDetail());

        self::assertSame($error, $error->setDetail('test'));
        self::assertEquals('test', $error->getDetail());
    }

    public function testSource()
    {
        $error = new Error();
        self::assertNull($error->getSource());

        $source = new ErrorSource();
        self::assertSame($error, $error->setSource($source));
        self::assertSame($source, $error->getSource());

        $error->setSource(null);
        self::assertNull($error->getSource());
    }

    public function testInnerException()
    {
        $error = new Error();
        self::assertNull($error->getInnerException());

        $exception = new \Exception();
        self::assertSame($error, $error->setInnerException($exception));
        self::assertSame($exception, $error->getInnerException());

        $error->setInnerException(null);
        self::assertNull($error->getInnerException());
    }

    public function testTrans()
    {
        $error = new Error();
        $error->setTitle(new Label('title'));
        $error->setDetail(new Label('detail'));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::at(0))
            ->method('trans')
            ->with('title')
            ->willReturn('translated_title');
        $translator->expects(self::at(1))
            ->method('trans')
            ->with('detail')
            ->willReturn('translated_detail');

        $error->trans($translator);
        self::assertEquals('translated_title', $error->getTitle());
        self::assertEquals('translated_detail', $error->getDetail());
    }

    public function testTransWhenNothingToTranslate()
    {
        $error = new Error();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())
            ->method('trans');

        $error->trans($translator);
        self::assertNull($error->getTitle());
        self::assertNull($error->getDetail());
    }
}
