<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\MetaOperationParser;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MetaOperationParserTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyMeta(): void
    {
        $meta = [];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertSame([null, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    public function testUpdateMetaOptionEqualsToTrue(): void
    {
        $meta = [
            'update' => true
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertSame([true, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    public function testUpdateMetaOptionEqualsToFalse(): void
    {
        $meta = [
            'update' => false
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertSame([false, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    /**
     * @dataProvider invalidUpdateOptionDataProvider
     */
    public function testInvalidUpdateMetaOption(mixed $updateOptionValue): void
    {
        $meta = [
            'update' => $updateOptionValue
        ];
        $error = Error::createValidationError(
            Constraint::VALUE,
            'This value should be a boolean.'
        )->setSource(ErrorSource::createByPointer('/meta/update'));

        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('addError')
            ->with($error);

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertNull($flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    public function invalidUpdateOptionDataProvider(): array
    {
        return [
            [null],
            ['test']
        ];
    }

    public function testUpsertMetaOptionEqualsToTrue(): void
    {
        $meta = [
            'upsert' => true
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertSame([null, true], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    public function testUpsertMetaOptionEqualsToFalse(): void
    {
        $meta = [
            'upsert' => false
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertSame([null, false], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    public function testUpsertMetaOptionEqualsToIdArray(): void
    {
        $meta = [
            'upsert' => ['id']
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertSame([null, true], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    public function testUpsertMetaOptionEqualsToArrayOfFields(): void
    {
        $meta = [
            'upsert' => ['field1', 'field2']
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertSame([null, ['field1', 'field2']], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    /**
     * @dataProvider invalidUpsertOptionDataProvider
     */
    public function testInvalidUpsertMetaOption(mixed $upsertOptionValue): void
    {
        $meta = [
            'upsert' => $upsertOptionValue
        ];
        $error = Error::createValidationError(
            Constraint::VALUE,
            'This value should be a boolean or an array of strings.'
        )->setSource(ErrorSource::createByPointer('/meta/upsert'));

        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('addError')
            ->with($error);

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertNull($flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    public function invalidUpsertOptionDataProvider(): array
    {
        return [
            [null],
            ['test'],
            [[]],
            [['field1', '']],
            [['field1', ' ']],
            [['field1', 123]],
            [['key1' => 'val1', 'key2' => 'val2']]
        ];
    }

    public function testBothUpdateAndUpsertMetaOptionsEqualsToTrue(): void
    {
        $meta = [
            'update' => true,
            'upsert' => true
        ];
        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'Both "update" and "upsert" options cannot be set.'
        )->setSource(ErrorSource::createByPointer('/meta'));

        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('addError')
            ->with($error);

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertNull($flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }

    public function testBothUpdateAndUpsertMetaOptionsEqualsToFalse(): void
    {
        $meta = [
            'update' => false,
            'upsert' => false
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', '/meta', $context);

        self::assertSame([false, false], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert'));
    }
}
