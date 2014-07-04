<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class FileNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileNormalizer */
    protected $normalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    public function setUp()
    {
        $this->normalizer = new FileNormalizer();

        $filesystemMap = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();

        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMap->expects($this->any())->method('get')
            ->will($this->returnValue($filesystem));

        $filesystem->expects($this->any())->method('getAdapter')
            ->will($this->returnValue(new \stdClass()));

        $router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $serviceLink->expects($this->any())->method('getService')
            ->will($this->returnValue($securityFacade));

        $securityFacade->expects($this->any())->method('getLoggedUser')
            ->will($this->returnValue(null));

        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->setConstructorArgs([$filesystemMap, $router, $serviceLink, []])
            ->setMethods(['upload', 'getAttachment'])
            ->getMock();

        $this->validator = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer->setAttachmentManager($this->attachmentManager);
        $this->normalizer->setValidator($this->validator);
    }

    /**
     * @dataProvider supportsDenormalizationData
     */
    public function testSupportsDenormalization($type, $isSupport)
    {
        $this->assertEquals($isSupport, $this->normalizer->supportsDenormalization([], $type));
    }

    public function supportsDenormalizationData()
    {
        return [
            'supports' => ['Oro\Bundle\AttachmentBundle\Entity\File', true],
            'notSupports' => ['testClass', false],
        ];
    }

    /**
     * @dataProvider supportsNormalizationData
     */
    public function testSupportsNormalization($data, $isSupport)
    {
        $this->assertEquals($isSupport, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationData()
    {
        return [
            'supports' => [new File(), true],
            'wrongObject' => [new \stdClass(), false],
            'notObject' => ['test', false]
        ];
    }

    /**
     * @dataProvider denormalizationData
     */
    public function testDenormalize($data, $context, $violations, $upload, $expects)
    {
        if ($violations) {
            $this->validator->expects($this->once())->method('validate')
                ->with($context['entityName'], $context['fieldName'])
                ->will($this->returnValue($violations));
        } else {
            $this->validator->expects($this->never())->method('validate');
        }

        if ($upload) {
            $this->attachmentManager->expects($this->once())
                ->method('upload');
        }

        $result = $this->normalizer->denormalize($data, '', '', $context);
        if ($expects) {
            $accessor = PropertyAccess::createPropertyAccessor();
            foreach ($expects as $fieldName => $value) {
                $this->assertEquals($value, $accessor->getValue($result, $fieldName));
            }
        } else {
            $this->assertNull($result);
        }
    }

    public function denormalizationData()
    {
        return [
            'correctData' => [
                realpath(__DIR__ . '/../Fixtures/testFile/test.txt'),
                ['entityName' => 'testEntity', 'fieldName' => 'testField'],
                new ConstraintViolationList(),
                true,
                [
                    'extension' => 'txt',
                    'mimeType' => 'text/plain',
                    'originalFilename' => 'test.txt',
                    'fileSize' => 9
                ]
            ],
            'wrongFIle' => [
                realpath('test.txt'),
                ['entityName' => 'testEntity', 'fieldName' => 'testField'],
                null,
                false,
                []
            ],
            'notValidatedFile' => [
                realpath(__DIR__ . '/../Fixtures/testFile/test.txt'),
                ['entityName' => 'testEntity', 'fieldName' => 'testField'],
                new ConstraintViolationList([new ConstraintViolation('', '', [], '', '', '')]),
                false,
                []
            ],
        ];
    }

    public function testNormalize()
    {
        $object = new File();
        $this->attachmentManager->expects($this->once())->method('getAttachment')
            ->with('testEntity', 1, 'testField', $object, 'download', true);
        $this->normalizer->normalize(
            $object,
            null,
            [
                'entityName' => 'testEntity',
                'entityId' => 1,
                'fieldName' => 'testField'
            ]
        );
    }
}
