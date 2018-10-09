<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ProtocolNotSupportedException;
use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class FileNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileNormalizer */
    protected $normalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    public function setUp()
    {
        $this->normalizer = new FileNormalizer();

        $router = $this->createMock(Router::class);
        $associationManager = $this->createMock(AssociationManager::class);
        $this->attachmentManager = $this->getMockBuilder(AttachmentManager::class)
            ->setConstructorArgs([$router, [], $associationManager, true, true])
            ->setMethods(['getAttachment'])
            ->getMock();
        $this->fileManager = $this->createMock(FileManager::class);
        $this->validator = $this->createMock(ConfigFileValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->normalizer->setAttachmentManager($this->attachmentManager);
        $this->normalizer->setFileManager($this->fileManager);
        $this->normalizer->setValidator($this->validator);
        $this->normalizer->setLogger($this->logger);
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
            'supports' => [File::class, true],
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
        $this->logger->expects($this->never())
            ->method('error');

        $result = $this->normalizer->denormalize($data, '', '', $context);
        $this->assertSame($entity, $result);
    }

    public function testDenormalizeNotSupportedFileProtocol()
    {
        $data = 'http://example.com/test.txt';
        $context = ['entityName' => 'testEntity', 'fieldName' => 'testField'];

        $this->fileManager->expects($this->once())
            ->method('createFileEntity')
            ->with($data)
            ->willThrowException(new ProtocolNotSupportedException($data));
        $this->validator->expects($this->never())
            ->method('validate');
        $this->logger->expects($this->once())
            ->method('error')
            ->with('The protocol for the file "http://example.com/test.txt" is not supported.');

        $result = $this->normalizer->denormalize($data, '', '', $context);
        $this->assertNull($result);
    }

    public function testDenormalizeNotExistingFile()
    {
        $data = 'http://example.com/test.txt';
        $context = ['entityName' => 'testEntity', 'fieldName' => 'testField'];

        $this->fileManager->expects($this->once())
            ->method('createFileEntity')
            ->with($data)
            ->willThrowException(new FileNotFoundException('File does not exist.', 0, null, $data));
        $this->validator->expects($this->never())
            ->method('validate');
        $this->logger->expects($this->once())
            ->method('error')
            ->with('File does not exist.');

        $result = $this->normalizer->denormalize($data, '', '', $context);
        $this->assertNull($result);
    }

    public function testDenormalizeNotValidFile()
    {
        $data = 'http://example.com/test.txt';
        $context = ['entityName' => 'testEntity', 'fieldName' => 'testField'];
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Some constraint violation', '', [], '', '', '')
        ]);

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
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Some constraint violation.'));

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
