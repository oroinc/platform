<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;

class ConfigFileValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigFileValidator */
    protected $configValidator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentConfigProvider;

    public function setUp()
    {
        $this->validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');
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
        $entityAttachmentConfig->expects($this->once())
            ->method('get')
            ->with('maxsize')
            ->will($this->returnValue($maxsize));

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
