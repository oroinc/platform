<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Model;

use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use PHPUnit\Framework\TestCase;

final class FileValidationTargetTest extends TestCase
{
    public function testSystem(): void
    {
        $target = FileValidationTarget::system();

        self::assertTrue($target->isSystem());
        self::assertSame('', $target->entityClass);
        self::assertSame('', $target->fieldName);
    }

    public function testIsNotSystemWhenEntityClassIsSpecified(): void
    {
        $target = new FileValidationTarget(\stdClass::class);

        self::assertFalse($target->isSystem());
        self::assertSame(\stdClass::class, $target->entityClass);
        self::assertSame('', $target->fieldName);
    }

    public function testIsNotSystemWhenEntityClassAndFieldNameAreSpecified(): void
    {
        $target = new FileValidationTarget(\stdClass::class, 'avatar');

        self::assertFalse($target->isSystem());
        self::assertSame('avatar', $target->fieldName);
    }

    public function testFieldNameWithoutEntityClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The field name cannot be specified without the entity class.');

        new FileValidationTarget('', 'avatar');
    }

    /**
     * @dataProvider getKeyDataProvider
     */
    public function testGetKey(string $entityClass, string $fieldName, string $expected): void
    {
        self::assertSame($expected, (new FileValidationTarget($entityClass, $fieldName))->getKey());
    }

    public function getKeyDataProvider(): array
    {
        return [
            'entity and field' => [
                'entityClass' => 'Test\\Entity',
                'fieldName' => 'avatar',
                'expected' => 'Test\\Entity|avatar',
            ],
            'entity without field' => [
                'entityClass' => 'Test\\Entity',
                'fieldName' => '',
                'expected' => 'Test\\Entity|',
            ],
            'system' => [
                'entityClass' => '',
                'fieldName' => '',
                'expected' => '|',
            ],
        ];
    }
}
