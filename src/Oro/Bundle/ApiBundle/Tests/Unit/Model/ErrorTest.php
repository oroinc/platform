<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorMetaProperty;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Label;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ErrorTest extends TestCase
{
    public function testCreate(): void
    {
        $error = Error::create('title', 'detail');
        self::assertEquals('title', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateValidationError(): void
    {
        $error = Error::createValidationError('title', 'detail');
        self::assertEquals(400, $error->getStatusCode());
        self::assertEquals('title', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateConflictValidationError(): void
    {
        $error = Error::createConflictValidationError('detail');
        self::assertEquals(409, $error->getStatusCode());
        self::assertEquals('conflict constraint', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateByException(): void
    {
        $exception = new \Exception();
        $error = Error::createByException($exception);
        self::assertSame($exception, $error->getInnerException());
    }

    public function testStatusCode(): void
    {
        $error = new Error();
        self::assertNull($error->getStatusCode());

        self::assertSame($error, $error->setStatusCode(400));
        self::assertEquals(400, $error->getStatusCode());
    }

    public function testCode(): void
    {
        $error = new Error();
        self::assertNull($error->getCode());

        self::assertSame($error, $error->setCode('test'));
        self::assertEquals('test', $error->getCode());
    }

    public function testTitle(): void
    {
        $error = new Error();
        self::assertNull($error->getTitle());

        self::assertSame($error, $error->setTitle('test'));
        self::assertEquals('test', $error->getTitle());
    }

    public function testDetail(): void
    {
        $error = new Error();
        self::assertNull($error->getDetail());

        self::assertSame($error, $error->setDetail('test'));
        self::assertEquals('test', $error->getDetail());
    }

    public function testSource(): void
    {
        $error = new Error();
        self::assertNull($error->getSource());

        $source = new ErrorSource();
        self::assertSame($error, $error->setSource($source));
        self::assertSame($source, $error->getSource());

        $error->setSource(null);
        self::assertNull($error->getSource());
    }

    public function testInnerException(): void
    {
        $error = new Error();
        self::assertNull($error->getInnerException());

        $exception = new \Exception();
        self::assertSame($error, $error->setInnerException($exception));
        self::assertSame($exception, $error->getInnerException());

        $error->setInnerException(null);
        self::assertNull($error->getInnerException());
    }

    public function testMetaProperties(): void
    {
        $error = new Error();
        self::assertSame([], $error->getMetaProperties());

        $metaProperty = new ErrorMetaProperty('val1');
        self::assertSame($error, $error->addMetaProperty('meta1', $metaProperty));
        self::assertSame(['meta1' => $metaProperty], $error->getMetaProperties());

        self::assertSame($error, $error->removeMetaProperty('meta1'));
        self::assertSame([], $error->getMetaProperties());
    }

    public function testTrans(): void
    {
        $error = new Error();
        $error->setTitle(new Label('title'));
        $error->setDetail(new Label('detail'));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return 'translated_' . $id;
            });

        $error->trans($translator);
        self::assertEquals('translated_title', $error->getTitle());
        self::assertEquals('translated_detail', $error->getDetail());
    }

    public function testTransWhenNothingToTranslate(): void
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
