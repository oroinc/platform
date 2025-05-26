<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Exception\ValidationExceptionInterface;
use PHPUnit\Framework\TestCase;

class NotSupportedConfigOperationExceptionTest extends TestCase
{
    public function testShouldImplementValidationExceptionInterface(): void
    {
        $exception = new NotSupportedConfigOperationException('Test\Class', 'test_operation');
        self::assertInstanceOf(ValidationExceptionInterface::class, $exception);
    }

    public function testShouldBuildMessage(): void
    {
        $exception = new NotSupportedConfigOperationException('Test\Class', 'test_operation');
        self::assertEquals(
            'Requested unsupported operation "test_operation" when building config for "Test\Class".',
            $exception->getMessage()
        );
    }

    public function testShouldSetClassName(): void
    {
        $exception = new NotSupportedConfigOperationException('Test\Class', 'test_operation');
        self::assertEquals('Test\Class', $exception->getClassName());
    }

    public function testShouldSetOperation(): void
    {
        $exception = new NotSupportedConfigOperationException('Test\Class', 'test_operation');
        self::assertEquals('test_operation', $exception->getOperation());
    }
}
