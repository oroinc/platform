<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromEntityFieldConfig;
use PHPUnit\Framework\TestCase;

class FileConstraintFromEntityFieldConfigTest extends TestCase
{
    private const string SAMPLE_CLASS = 'SampleClass';
    private const string SAMPLE_FIELD = 'sampleField';

    private FileConstraintFromEntityFieldConfig $constraint;

    #[\Override]
    protected function setUp(): void
    {
        $this->constraint = new FileConstraintFromEntityFieldConfig(
            ['entityClass' => self::SAMPLE_CLASS, 'fieldName' => self::SAMPLE_FIELD]
        );
    }

    public function testConstructWhenNoEntityClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option entityClass is required');

        new FileConstraintFromEntityFieldConfig();
    }

    public function testConstructWhenNoFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option fieldName is required');

        new FileConstraintFromEntityFieldConfig(['entityClass' => self::SAMPLE_CLASS]);
    }

    public function testGetEntityClass(): void
    {
        $this->assertEquals(self::SAMPLE_CLASS, $this->constraint->getEntityClass());
    }

    public function testGetFieldName(): void
    {
        $this->assertEquals(self::SAMPLE_FIELD, $this->constraint->getFieldName());
    }
}
