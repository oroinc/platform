<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager as Configuration;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigFileValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigFileValidator */
    private $configValidator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $config;

    public function setUp()
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->config = $this->createMock(Configuration::class);

        $this->configValidator = new ConfigFileValidator($this->validator, $this->configManager, $this->config);
    }

    public function testValidateForFileFieldWithoutMimeTypes()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $fieldName = 'testField';
        $mimeTypes = "image/jpeg\ntext/plain";
        $maxSize = 1; // 1Mb

        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $dataClass, $fieldName)
            ->willReturn(new Config(
                new FieldConfigId('attachment', $dataClass, $fieldName, 'file'),
                ['maxsize' => $maxSize]
            ));
        $this->config->expects($this->once())
            ->method('get')
            ->with('oro_attachment.upload_file_mime_types')
            ->willReturn($mimeTypes);

        $violationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint([
                        'maxSize'   => $maxSize * 1024 * 1024,
                        'mimeTypes' => explode("\n", $mimeTypes)
                    ])
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass, $fieldName);
        $this->assertSame($violationList, $result);
    }

    public function testValidateForImageFieldWithoutMimeTypes()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $fieldName = 'testField';
        $mimeTypes = "image/jpeg\ntext/plain";
        $maxSize = 1; // 1Mb

        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $dataClass, $fieldName)
            ->willReturn(new Config(
                new FieldConfigId('attachment', $dataClass, $fieldName, 'image'),
                ['maxsize' => $maxSize]
            ));
        $this->config->expects($this->once())
            ->method('get')
            ->with('oro_attachment.upload_image_mime_types')
            ->willReturn($mimeTypes);

        $violationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint([
                        'maxSize'   => $maxSize * 1024 * 1024,
                        'mimeTypes' => explode("\n", $mimeTypes)
                    ])
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass, $fieldName);
        $this->assertSame($violationList, $result);
    }

    public function testValidateForFileFieldWithMimeTypes()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $fieldName = 'testField';
        $fieldMimeTypes = "image/jpeg\ntext/plain";
        $maxSize = 1; // 1Mb

        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $dataClass, $fieldName)
            ->willReturn(new Config(
                new FieldConfigId('attachment', $dataClass, $fieldName, 'file'),
                ['mimetypes' => $fieldMimeTypes, 'maxsize' => $maxSize]
            ));
        $this->config->expects(static::never())
            ->method('get');

        $violationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(static::once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint([
                        'maxSize'   => $maxSize * 1024 * 1024,
                        'mimeTypes' => explode("\n", $fieldMimeTypes)
                    ])
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass, $fieldName);
        $this->assertSame($violationList, $result);
    }

    public function testValidateWithEmptyFieldNameWithoutMaxSizeAndMimeTypes()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $mimeTypes = "image/jpeg\ntext/plain";

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('attachment', $dataClass)
            ->willReturn(new Config(
                new EntityConfigId('attachment', $dataClass)
            ));
        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_attachment.upload_file_mime_types', false, false, null, $mimeTypes],
                ['oro_attachment.upload_image_mime_types', false, false, null, $mimeTypes]
            ]);

        $violationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint([
                        'maxSize'   => null,
                        'mimeTypes' => explode("\n", $mimeTypes)
                    ])
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass);
        $this->assertSame($violationList, $result);
    }

    public function testValidateWithEmptyFieldNameWithoutMimeTypes()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $mimeTypes = "image/jpeg\ntext/plain";
        $maxSize = 1; // 1Mb

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('attachment', $dataClass)
            ->willReturn(new Config(
                new EntityConfigId('attachment', $dataClass),
                ['maxsize' => $maxSize]
            ));
        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_attachment.upload_file_mime_types', false, false, null, $mimeTypes],
                ['oro_attachment.upload_image_mime_types', false, false, null, $mimeTypes]
            ]);

        $violationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint([
                        'maxSize'   => $maxSize * 1024 * 1024,
                        'mimeTypes' => explode("\n", $mimeTypes)
                    ])
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass);
        $this->assertSame($violationList, $result);
    }

    public function testValidateWithEmptyFieldNameAndMimeTypes()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $entityMimeTypes = "image/jpeg\ntext/plain";
        $maxSize = 1; // 1Mb

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('attachment', $dataClass)
            ->willReturn(new Config(
                new EntityConfigId('attachment', $dataClass),
                ['mimetypes' => $entityMimeTypes, 'maxsize' => $maxSize]
            ));
        $this->config->expects($this->never())
            ->method('get');

        $violationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint([
                        'maxSize'   => $maxSize * 1024 * 1024,
                        'mimeTypes' => explode("\n", $entityMimeTypes)
                    ])
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass);
        $this->assertSame($violationList, $result);
    }
}
