<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\ConstraintViolationList;

class ConfigFileValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigFileValidator */
    protected $configValidator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $config;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $attachmentConfigProvider;

    public function setUp()
    {
        $this->validator = $this->createMock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $this->config = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('getProvider')
            ->with('attachment')
            ->will($this->returnValue($this->attachmentConfigProvider));
        $this->configValidator = new ConfigFileValidator($this->validator, $configManager, $this->config);
    }

    public function testValidate()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $fieldName = 'testField';
        $mimeTypes = "image/*\ntext/plain";
        $maxsize = 1; // 1Mb

        $entityAttachmentConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($dataClass)
            ->will($this->returnValue($entityAttachmentConfig));
        $fieldConfigId = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();
        $entityAttachmentConfig->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($fieldConfigId));
        $fieldConfigId->expects($this->once())
            ->method('getFieldType')
            ->will($this->returnValue('file'));
        $this->config->expects($this->once())
            ->method('get')
            ->with('oro_attachment.upload_file_mime_types')
            ->will($this->returnValue($mimeTypes));
        $entityAttachmentConfig->expects(static::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls('', $maxsize);

        $violationList = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationList')
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint(
                        [
                            'maxSize'   => $maxsize * 1024 * 1024,
                            'mimeTypes' => explode("\n", $mimeTypes)
                        ]
                    )
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass, $fieldName);
        $this->assertSame($violationList, $result);
    }

    public function testValidateFieldWithMIMEType()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $fieldName = 'testField';
        $mimeTypes = "image/*\ntext/plain";
        $fieldMimeType = 'image/svg1';
        $maxSize = 1;
        $entityAttachmentConfig = $this->createMock(Config::class);
        $this->attachmentConfigProvider->expects(static::once())
            ->method('getConfig')
            ->with($dataClass)
            ->will($this->returnValue($entityAttachmentConfig));
        $fieldConfigId = $this->createMock(FieldConfigId::class);
        $entityAttachmentConfig->expects(static::never())
            ->method('getId')
            ->will($this->returnValue($fieldConfigId));
        $fieldConfigId->expects(static::never())
            ->method('getFieldType')
            ->will($this->returnValue('file'));
        $this->config->expects(static::never())
            ->method('get')
            ->with('oro_attachment.upload_file_mime_types')
            ->will($this->returnValue($mimeTypes));
        $entityAttachmentConfig->expects(static::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($fieldMimeType, $maxSize);

        $violationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(static::once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint(
                        [
                            'maxSize'   => $maxSize * 1024 * 1024,
                            'mimeTypes' => explode("\n", $fieldMimeType)
                        ]
                    )
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass, $fieldName);
        $this->assertSame($violationList, $result);
    }

    public function testValidateWithEmptyFieldName()
    {
        $dataClass = 'testClass';
        $file = new ComponentFile(
            realpath(__DIR__ . '/../Fixtures/testFile/test.txt')
        );
        $fieldName = '';
        $mimeTypes = "image/*\ntext/plain";
        $maxsize = 1; // 1Mb

        $entityAttachConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($dataClass)
            ->will($this->returnValue($entityAttachConfig));
        $entityAttachConfig->expects($this->any())
            ->method('get')
            ->willReturnOnConsecutiveCalls(false, $maxsize);
        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls('', $mimeTypes);

        $violationList = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationList')
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($file),
                [
                    new FileConstraint(
                        [
                            'maxSize'   => $maxsize * 1024 * 1024,
                            'mimeTypes' => explode("\n", $mimeTypes)
                        ]
                    )
                ]
            )
            ->willReturn($violationList);

        $result = $this->configValidator->validate($file, $dataClass, $fieldName);
        $this->assertSame($violationList, $result);
    }
}
