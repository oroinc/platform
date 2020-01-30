<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Helper;

use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\Entity\TestEntity1;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class FieldConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isImageFieldProvider
     *
     * @param string $inputType
     * @param bool $expectedResult
     */
    public function testIsImageField(string $inputType, bool $expectedResult)
    {
        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'fieldName', $inputType);

        $this->assertEquals($expectedResult, FieldConfigHelper::isImageField($fieldConfigId));
    }

    /**
     * @return array
     */
    public function isImageFieldProvider(): array
    {
        return [
            'image' => [
                'input' => FieldConfigHelper::IMAGE_TYPE,
                'expected' => true,
            ],
            'multiImage' => [
                'input' => FieldConfigHelper::MULTI_IMAGE_TYPE,
                'expected' => true,
            ],
            'integer' => [
                'input' => 'integer',
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider isFileFieldProvider
     *
     * @param string $inputType
     * @param bool $expectedResult
     */
    public function testIsFileField(string $inputType, bool $expectedResult)
    {
        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'fieldName', $inputType);

        $this->assertEquals($expectedResult, FieldConfigHelper::isFileField($fieldConfigId));
    }

    /**
     * @return array
     */
    public function isFileFieldProvider(): array
    {
        return [
            'file' => [
                'input' => FieldConfigHelper::FILE_TYPE,
                'expected' => true,
            ],
            'multiFile' => [
                'input' => FieldConfigHelper::MULTI_FILE_TYPE,
                'expected' => true,
            ],
            'integer' => [
                'input' => 'integer',
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider isMultiFieldProvider
     *
     * @param string $inputType
     * @param bool $expectedResult
     */
    public function testIsMultiField(string $inputType, bool $expectedResult)
    {
        $fieldConfigId = new FieldConfigId('extend', TestEntity1::class, 'fieldName', $inputType);

        $this->assertEquals($expectedResult, FieldConfigHelper::isMultiField($fieldConfigId));
    }

    /**
     * @return array
     */
    public function isMultiFieldProvider(): array
    {
        return [
            'multiFile' => [
                'input' => FieldConfigHelper::MULTI_FILE_TYPE,
                'expected' => true,
            ],
            'multiImage' => [
                'input' => FieldConfigHelper::MULTI_IMAGE_TYPE,
                'expected' => true,
            ],
            'file' => [
                'input' => FieldConfigHelper::FILE_TYPE,
                'expected' => false,
            ],
            'image' => [
                'input' => FieldConfigHelper::IMAGE_TYPE,
                'expected' => false,
            ],
            'integer' => [
                'input' => 'integer',
                'expected' => false,
            ],
        ];
    }
}
