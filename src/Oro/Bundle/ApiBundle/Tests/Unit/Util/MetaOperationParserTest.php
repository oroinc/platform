<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\MetaOperationParser;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MetaOperationParserTest extends TestCase
{
    public function testGetOperationFlagsForEmptyMeta(): void
    {
        $meta = [];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([null, null, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenUpdateMetaOptionEqualsToTrue(): void
    {
        $meta = [
            'update' => true
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([true, null, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenUpdateMetaOptionEqualsToFalse(): void
    {
        $meta = [
            'update' => false
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([false, null, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    /**
     * @dataProvider invalidUpdateOptionDataProvider
     */
    public function testGetOperationFlagsWhenInvalidUpdateMetaOption(mixed $updateOptionValue): void
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

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertNull($flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function invalidUpdateOptionDataProvider(): array
    {
        return [
            [null],
            ['test']
        ];
    }

    public function testGetOperationFlagsWhenUpsertMetaOptionEqualsToTrue(): void
    {
        $meta = [
            'upsert' => true
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([null, true, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenUpsertMetaOptionEqualsToFalse(): void
    {
        $meta = [
            'upsert' => false
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([null, false, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenUpsertMetaOptionEqualsToIdArray(): void
    {
        $meta = [
            'upsert' => ['id']
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([null, true, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenUpsertMetaOptionEqualsToArrayOfFields(): void
    {
        $meta = [
            'upsert' => ['field1', 'field2']
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([null, ['field1', 'field2'], null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    /**
     * @dataProvider invalidUpsertOptionDataProvider
     */
    public function testGetOperationFlagsWhenInvalidUpsertMetaOption(mixed $upsertOptionValue): void
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

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertNull($flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
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

    public function testGetOperationFlagsWhenValidateMetaOptionEqualsToTrue(): void
    {
        $meta = [
            'validate' => true
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([null, null, true], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenValidateMetaOptionEqualsToFalse(): void
    {
        $meta = [
            'validate' => false
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([null, null, false], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    /**
     * @dataProvider invalidUpdateOptionDataProvider
     */
    public function testGetOperationFlagsWhenInvalidValidateMetaOption(mixed $validateOptionValue): void
    {
        $meta = [
            'validate' => $validateOptionValue
        ];
        $error = Error::createValidationError(
            Constraint::VALUE,
            'This value should be a boolean.'
        )->setSource(ErrorSource::createByPointer('/meta/validate'));

        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('addError')
            ->with($error);

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertNull($flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenBothUpdateAndUpsertMetaOptionsEqualsToTrue(): void
    {
        $meta = [
            'update' => true,
            'upsert' => true
        ];
        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'Only one meta option can be used.'
        )->setSource(ErrorSource::createByPointer('/meta'));

        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('addError')
            ->with($error);

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertNull($flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenBothUpdateAndUpsertMetaOptionsEqualsToFalse(): void
    {
        $meta = [
            'update' => false,
            'upsert' => false
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([false, false, null], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenAllMetaOptionsEqualsToTrue(): void
    {
        $meta = [
            'update' => true,
            'upsert' => true,
            'validate' => true
        ];
        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'Only one meta option can be used.'
        )->setSource(ErrorSource::createByPointer('/meta'));

        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('addError')
            ->with($error);

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertNull($flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testGetOperationFlagsWhenValidateMetaOptionsEqualsToTrueWithFalseOptions(): void
    {
        $meta = [
            'update' => false,
            'upsert' => false,
            'validate' => true
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        $flags = MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate', '/meta', $context);

        self::assertSame([false, false, true], $flags);

        self::assertSame($flags, MetaOperationParser::getOperationFlags($meta, 'update', 'upsert', 'validate'));
    }

    public function testAssertOperationFlagNotExistsForEmptyMeta(): void
    {
        $meta = [];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        self::assertTrue(MetaOperationParser::assertOperationFlagNotExists($meta, 'update', '/meta', $context));
        self::assertTrue(MetaOperationParser::assertOperationFlagNotExists($meta, 'update'));
    }

    public function testAssertOperationFlagNotExistsWhenFlagDoesNotExist(): void
    {
        $meta = [
            'upsert' => true
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::never())
            ->method('addError');

        self::assertTrue(MetaOperationParser::assertOperationFlagNotExists($meta, 'update', '/meta', $context));
        self::assertTrue(MetaOperationParser::assertOperationFlagNotExists($meta, 'update'));
    }

    /**
     * @dataProvider assertOperationFlagNotExistsWhenFlagExistsDataProvider
     */
    public function testAssertOperationFlagNotExistsWhenFlagExists(array $meta): void
    {
        $error = Error::createValidationError(
            Constraint::VALUE,
            'The option is not supported.'
        )->setSource(ErrorSource::createByPointer('/meta/update'));

        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('addError')
            ->with($error);

        self::assertFalse(MetaOperationParser::assertOperationFlagNotExists($meta, 'update', '/meta', $context));
        self::assertFalse(MetaOperationParser::assertOperationFlagNotExists($meta, 'update'));
    }

    public static function assertOperationFlagNotExistsWhenFlagExistsDataProvider(): array
    {
        return [
            [['update' => null]],
            [['update' => false]],
            [['update' => true]]
        ];
    }
}
