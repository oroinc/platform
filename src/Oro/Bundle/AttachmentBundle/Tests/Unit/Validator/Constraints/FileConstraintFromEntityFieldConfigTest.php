<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromEntityFieldConfig;

class FileConstraintFromEntityFieldConfigTest extends \PHPUnit\Framework\TestCase
{
    private const SAMPLE_CLASS = 'SampleClass';
    private const SAMPLE_FIELD = 'sampleField';

    /** @var FileConstraintFromEntityFieldConfig */
    private $constraint;

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
