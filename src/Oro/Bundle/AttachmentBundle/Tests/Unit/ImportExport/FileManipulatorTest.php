<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper;
use Oro\Bundle\AttachmentBundle\ImportExport\FileManipulator;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileManipulatorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const ENTITY_CLASS = 'SampleClass';
    private const FIELD_NAME = 'sampleField';
    private const FIELD_LABEL = 'Sample Label';

    private FileManager|\PHPUnit\Framework\MockObject\MockObject $fileManager;

    private AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker;

    private FileImportStrategyHelper|\PHPUnit\Framework\MockObject\MockObject $fileImportStrategyHelper;

    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    private FileManipulator $manipulator;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->fileImportStrategyHelper = $this->createMock(FileImportStrategyHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->fileImportStrategyHelper
            ->expects(self::any())
            ->method('getClass')
            ->willReturn(self::ENTITY_CLASS);

        $this->fileImportStrategyHelper
            ->expects(self::any())
            ->method('getFieldLabel')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(self::FIELD_LABEL);

        $this->manipulator = new FileManipulator(
            $this->fileManager,
            $this->authorizationChecker,
            $this->fileImportStrategyHelper,
            $this->translator
        );

        $this->setUpLoggerMock($this->manipulator);
    }

    public function testSetFileFromOriginFileWhenNotGranted(): void
    {
        $originFile = (new TestFile())
            ->setId(42)
            ->setUuid(UUIDGenerator::v4());

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $originFile)
            ->willReturn(false);

        $parameters = [
            '%origin_id%' => $originFile->getId(),
            '%origin_uuid%' => $originFile->getUuid(),
            '%fieldname%' => self::FIELD_LABEL,
        ];

        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['oro.attachment.import.failed_to_clone_forbidden', $parameters],
                [
                    'oro.attachment.import.failed_to_clone',
                    $parameters + ['%error%' => 'oro.attachment.import.failed_to_clone_forbidden'],
                ]
            )
            ->willReturnArgument(0);

        self::assertEquals(
            ['oro.attachment.import.failed_to_clone'],
            $this->manipulator->setFileFromOriginFile(new File(), $originFile, new \stdClass(), self::FIELD_NAME)
        );
    }

    public function testSetFileFromOriginFileWhenNoFile(): void
    {
        $originFile = (new TestFile())
            ->setId(42)
            ->setUuid(UUIDGenerator::v4());

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $originFile)
            ->willReturn(true);

        $parameters = [
            '%origin_id%' => $originFile->getId(),
            '%origin_uuid%' => $originFile->getUuid(),
            '%fieldname%' => self::FIELD_LABEL,
        ];

        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['oro.attachment.import.failed_to_clone_origin_file_empty'],
                [
                    'oro.attachment.import.failed_to_clone',
                    $parameters + ['%error%' => 'oro.attachment.import.failed_to_clone_origin_file_empty'],
                ],
            )
            ->willReturnArgument(0);

        self::assertEquals(
            ['oro.attachment.import.failed_to_clone'],
            $this->manipulator->setFileFromOriginFile(new File(), $originFile, new \stdClass(), self::FIELD_NAME)
        );
    }

    public function testSetFileFromOriginFileWhenFailedToClone(): void
    {
        $originFile = (new TestFile())
            ->setId(42)
            ->setUuid(UUIDGenerator::v4());

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $originFile)
            ->willReturn(true);

        $exception = new \RuntimeException('Unexpected error');
        $this->fileManager
            ->expects(self::once())
            ->method('getFileFromFileEntity')
            ->with($originFile)
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Failed to clone a file during import', ['e' => $exception]);

        $parameters = [
            '%origin_id%' => $originFile->getId(),
            '%origin_uuid%' => $originFile->getUuid(),
            '%fieldname%' => self::FIELD_LABEL,
        ];

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('oro.attachment.import.failed_to_clone', $parameters + ['%error%' => $exception->getMessage()])
            ->willReturnArgument(0);

        self::assertEquals(
            ['oro.attachment.import.failed_to_clone'],
            $this->manipulator->setFileFromOriginFile(new File(), $originFile, new \stdClass(), self::FIELD_NAME)
        );
    }

    public function testSetFileFromOriginFileWhenSuccess(): void
    {
        $file = new File();
        $originFile = (new TestFile())
            ->setId(42)
            ->setUuid(UUIDGenerator::v4())
            ->setOriginalFilename('original-image.png');

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $originFile)
            ->willReturn(true);

        $innerFile = new \SplFileInfo('image.png');
        $this->fileManager
            ->expects(self::once())
            ->method('getFileFromFileEntity')
            ->with($originFile)
            ->willReturn($innerFile);

        self::assertEquals(
            [],
            $this->manipulator->setFileFromOriginFile($file, $originFile, new \stdClass(), self::FIELD_NAME)
        );

        self::assertSame($file->getFile(), $innerFile);
        self::assertEquals($file->getOriginalFilename(), $originFile->getOriginalFilename());
    }

    public function testSetFileFromUploadFileWhenFailedToSetFileFromPath(): void
    {
        $file = new File();
        $fileForUpload = new SymfonyFile('image.png', false);

        $exception = new \RuntimeException('Unexpected error');
        $this->fileManager
            ->expects(self::once())
            ->method('setFileFromPath')
            ->with($file, $fileForUpload)
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Failed to upload a file during import', ['e' => $exception]);

        $parameters = [
            '%fieldname%' => self::FIELD_LABEL,
            '%path%' => $fileForUpload->getPathname(),
            '%error%' => $exception->getMessage(),
        ];

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('oro.attachment.import.failed_to_upload', $parameters)
            ->willReturnArgument(0);

        self::assertEquals(
            ['oro.attachment.import.failed_to_upload'],
            $this->manipulator->setFileFromUpload($file, $fileForUpload, new \stdClass(), self::FIELD_NAME)
        );
    }

    public function testSetFileFromUploadFileWhenFailedToSetExternalFileFromUrl(): void
    {
        $file = new File();
        $fileForUpload = new ExternalFile('http://example.org/image.png');

        $exception = new \RuntimeException('Unexpected error');
        $this->fileManager
            ->expects(self::once())
            ->method('setExternalFileFromUrl')
            ->with($file, $fileForUpload->getUrl())
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Failed to upload a file during import', ['e' => $exception]);

        $parameters = [
            '%fieldname%' => self::FIELD_LABEL,
            '%path%' => $fileForUpload->getPathname(),
            '%error%' => $exception->getMessage(),
        ];

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('oro.attachment.import.failed_to_upload', $parameters)
            ->willReturnArgument(0);

        self::assertEquals(
            ['oro.attachment.import.failed_to_upload'],
            $this->manipulator->setFileFromUpload($file, $fileForUpload, new \stdClass(), self::FIELD_NAME)
        );
    }

    public function testSetFileFromUploadFileWhenUnsupportedFile(): void
    {
        $file = new File();
        $fileForUpload = new \SplFileInfo('image.png');

        $exception = new \LogicException(
            sprintf(
                'The object of type %s returned from %s is not supported. Expected one of %s',
                get_debug_type($fileForUpload),
                self::FIELD_LABEL,
                implode(', ', [SymfonyFile::class, ExternalFile::class])
            )
        );
        $this->fileManager
            ->expects(self::never())
            ->method(self::anything());

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Failed to upload a file during import', ['e' => $exception]);

        $parameters = [
            '%fieldname%' => self::FIELD_LABEL,
            '%path%' => $fileForUpload->getPathname(),
            '%error%' => $exception->getMessage(),
        ];

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('oro.attachment.import.failed_to_upload', $parameters)
            ->willReturnArgument(0);

        self::assertEquals(
            ['oro.attachment.import.failed_to_upload'],
            $this->manipulator->setFileFromUpload($file, $fileForUpload, new \stdClass(), self::FIELD_NAME)
        );
    }

    public function testSetFileFromUploadFileWhenFileFromPath(): void
    {
        $file = new File();
        $fileForUpload = new SymfonyFile('image.png', false);

        $this->fileManager
            ->expects(self::once())
            ->method('setFileFromPath')
            ->with($file, $fileForUpload);

        self::assertEquals(
            [],
            $this->manipulator->setFileFromUpload($file, $fileForUpload, new \stdClass(), self::FIELD_NAME)
        );
    }

    public function testSetFileFromUploadFileWhenExternalFileFromUrl(): void
    {
        $entity = new \stdClass();
        $file = new File();
        $fileForUpload = new ExternalFile('http://example.org/image.png');

        $this->fileImportStrategyHelper
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with($fileForUpload, $entity, self::FIELD_NAME)
            ->willReturn([]);

        $this->fileManager
            ->expects(self::once())
            ->method('setExternalFileFromUrl')
            ->with($file, $fileForUpload->getUrl());

        self::assertEquals(
            [],
            $this->manipulator->setFileFromUpload($file, $fileForUpload, $entity, self::FIELD_NAME)
        );
    }

    public function testSetFileFromUploadFileWhenExternalFileAndNotAcccessible(): void
    {
        $entity = new \stdClass();
        $file = new File();
        $fileForUpload = new ExternalFile('http://example.org/image.png');

        $this->fileImportStrategyHelper
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with($fileForUpload, $entity, self::FIELD_NAME)
            ->willReturn([]);

        $exception = new ExternalFileNotAccessibleException($fileForUpload->getUrl(), 'Not found');
        $this->fileManager
            ->expects(self::once())
            ->method('setExternalFileFromUrl')
            ->with($file, $fileForUpload->getUrl())
            ->willThrowException($exception);

        $parameters = [
            '%fieldname%' => self::FIELD_LABEL,
            '%url%' => $exception->getUrl(),
            '%error%' => $exception->getReason(),
        ];

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('oro.attachment.import.failed_to_process_external_file', $parameters)
            ->willReturnArgument(0);

        self::assertEquals(
            ['oro.attachment.import.failed_to_process_external_file'],
            $this->manipulator->setFileFromUpload($file, $fileForUpload, $entity, self::FIELD_NAME)
        );
    }

    public function testSetFileFromUploadFileWhenExternalFileAndNotValidUrl(): void
    {
        $entity = new \stdClass();
        $file = new File();
        $fileForUpload = new ExternalFile('http://example.org/image.png');
        $errorMessage = 'sample error';

        $this->fileImportStrategyHelper
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with($fileForUpload, $entity, self::FIELD_NAME)
            ->willReturn([$errorMessage]);

        $this->fileManager
            ->expects(self::never())
            ->method('setExternalFileFromUrl');

        self::assertEquals(
            [$errorMessage],
            $this->manipulator->setFileFromUpload($file, $fileForUpload, $entity, self::FIELD_NAME)
        );
    }
}
