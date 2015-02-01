<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;

class ConfigFileValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigFileValidator */
    protected $configValidator;

    /** @var Validator */
    protected $validator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentConfigProvider;

    public function setUp()
    {
        $this->validator = new Validator(
            new ClassMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new DefaultTranslator()
        );
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

    /**
     * @dataProvider validationData
     */
    public function testValidate($dataClass, $entity, $fieldName, $mimeTypes, $isValid)
    {
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
            ->will($this->returnValue(1));

        $result = $this->configValidator->validate($dataClass, $entity, $fieldName);
        if ($isValid) {
            $this->assertEquals(0, $result->count());
        } else {
            $this->assertNotEquals(0, $result->count());
        }
    }

    public function validationData()
    {
        $fileEntity = new File();
        $file = new \Symfony\Component\HttpFoundation\File\File(realpath(__DIR__ . '/../Fixtures/testFile/test.txt'));
        $fileEntity->setFile($file);
        return [
            'valid' => [
                'testClass',
                $fileEntity,
                'testField',
                "image/*\ntext/plain",
                true
            ],
            'bad_mime' => [
                'testClass',
                $fileEntity,
                'testField',
                "image/*",
                false
            ],
        ];
    }
}
