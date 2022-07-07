<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendReflectionErrorHandler;
use PHPUnit\Framework\TestCase;

class ExtendReflectionErrorHandlerTest extends TestCase
{
    /**
     * @dataProvider collectorDataProvider
     */
    public function testDataCollector(string $className, bool $expectedResult)
    {
        ExtendReflectionErrorHandler::dataCollector($className);
        $trace = ExtendReflectionErrorHandler::getTrace($className);

        if ($expectedResult) {
            self::assertIsArray($trace);
        } else {
            self::assertNull($trace);
        }
    }

    public function collectorDataProvider(): array
    {
        return [
            'Invalid class' => ['App\Bundle\AppBundle', false],
            'Valid class' => ['App\Bundle\Entity\Test', true],
        ];
    }

    /**
     * @dataProvider isSupportedDataProvider
     */
    public function testIsSupported(\Exception $exception, bool $expectedResult)
    {
        self::assertEquals($expectedResult, ExtendReflectionErrorHandler::isSupported($exception));
    }

    public function isSupportedDataProvider(): array
    {
        return  [
            'Not supported \Exception' => [new \Exception(), false],
            'Not supported \InvalidArgumentException ' => [new \InvalidArgumentException(), false],
            'Supported \ReflectionException with invalid trace' => [new \ReflectionException(), false],
            'Supported \ReflectionException with valid trace' => [$this->createValidException(), true]
        ];
    }

    private function createValidException(): \Throwable
    {
        return ExtendReflectionErrorHandler::buildException('Some message', [
            ['class' => 'ReflectionProperty', 'method' => '__construct']]);
    }

    public function testHandle()
    {
        $className = 'App\Bundle\Entity\Test';
        $exception = $this->createValidException();
        $result = ExtendReflectionErrorHandler::createException($className, $exception);

        self::assertInstanceOf('\ReflectionException', $result->getPrevious());
        self::assertStringContainsString($className, $result->getMessage());
        self::assertStringContainsString('with debug true.', $result->getMessage());

        ExtendReflectionErrorHandler::initialize();
        $result = ExtendReflectionErrorHandler::createException($className, $exception);

        self::assertInstanceOf('\ReflectionException', $result->getPrevious());
        self::assertStringContainsString($className, $result->getMessage());
    }
}
