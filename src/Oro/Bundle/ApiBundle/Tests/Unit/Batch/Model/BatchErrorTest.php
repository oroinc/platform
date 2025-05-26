<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Model;

use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use PHPUnit\Framework\TestCase;

class BatchErrorTest extends TestCase
{
    public function testCreate(): void
    {
        $error = BatchError::create('title', 'detail');
        self::assertInstanceOf(BatchError::class, $error);
        self::assertEquals('title', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateValidationError(): void
    {
        $error = BatchError::createValidationError('title', 'detail');
        self::assertInstanceOf(BatchError::class, $error);
        self::assertEquals(400, $error->getStatusCode());
        self::assertEquals('title', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateConflictValidationError(): void
    {
        $error = BatchError::createConflictValidationError('detail');
        self::assertInstanceOf(BatchError::class, $error);
        self::assertEquals(409, $error->getStatusCode());
        self::assertEquals('conflict constraint', $error->getTitle());
        self::assertEquals('detail', $error->getDetail());
    }

    public function testCreateByException(): void
    {
        $exception = new \Exception();
        $error = BatchError::createByException($exception);
        self::assertInstanceOf(BatchError::class, $error);
        self::assertSame($exception, $error->getInnerException());
    }

    public function testId(): void
    {
        $error = new BatchError();
        self::assertNull($error->getId());

        self::assertSame($error, $error->setId('test'));
        self::assertSame('test', $error->getId());

        self::assertSame($error, $error->setId(null));
        self::assertNull($error->getId());
    }

    public function testItemIndex(): void
    {
        $error = new BatchError();
        self::assertNull($error->getItemIndex());

        self::assertSame($error, $error->setItemIndex(123));
        self::assertSame(123, $error->getItemIndex());

        self::assertSame($error, $error->setItemIndex(null));
        self::assertNull($error->getItemIndex());
    }
}
