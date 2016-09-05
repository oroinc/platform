<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport;

use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;

class FileNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileNormalizer */
    protected $normalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $fileManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    public function setUp()
    {
        $this->normalizer = new FileNormalizer();

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

        $associationManager = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->setConstructorArgs([$router, [], $associationManager])
            ->setMethods(['getAttachment'])
            ->getMock();

        $this->fileManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\FileManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer->setAttachmentManager($this->attachmentManager);
        $this->normalizer->setFileManager($this->fileManager);
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

    public function testDenormalizeValidFile()
    {
        $data = 'http://example.com/test.txt';
        $context = ['entityName' => 'testEntity', 'fieldName' => 'testField'];
        $violations = new ConstraintViolationList();

        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);

        $this->fileManager->expects($this->any())
            ->method('createFileEntity')
            ->with($data)
            ->willReturn($entity);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->identicalTo($file), $context['entityName'], $context['fieldName'])
            ->will($this->returnValue($violations));

        $result = $this->normalizer->denormalize($data, '', '', $context);
        $this->assertSame($entity, $result);
    }

    public function testDenormalizeNotExistingFile()
    {
        $data = 'http://example.com/test.txt';
        $context = ['entityName' => 'testEntity', 'fieldName' => 'testField'];

        $this->fileManager->expects($this->any())
            ->method('createFileEntity')
            ->with($data)
            ->willReturn(null);
        $this->validator->expects($this->never())
            ->method('validate');

        $result = $this->normalizer->denormalize($data, '', '', $context);
        $this->assertNull($result);
    }

    public function testDenormalizeNotValidFile()
    {
        $data = 'http://example.com/test.txt';
        $context = ['entityName' => 'testEntity', 'fieldName' => 'testField'];
        $violations = new ConstraintViolationList([new ConstraintViolation('', '', [], '', '', '')]);

        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);

        $this->fileManager->expects($this->any())
            ->method('createFileEntity')
            ->with($data)
            ->willReturn($entity);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->identicalTo($file), $context['entityName'], $context['fieldName'])
            ->will($this->returnValue($violations));

        $result = $this->normalizer->denormalize($data, '', '', $context);
        $this->assertNull($result);
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
