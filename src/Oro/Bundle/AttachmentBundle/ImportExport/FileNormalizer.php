<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The normalizer for attached files.
 */
class FileNormalizer implements DenormalizerInterface, NormalizerInterface
{
    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var FileManager */
    protected $fileManager;

    /** @var ConfigFileValidator */
    protected $validator;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string|null */
    private $filesDir;

    /**
     * @param AttachmentManager $manager
     */
    public function setAttachmentManager(AttachmentManager $manager)
    {
        $this->attachmentManager = $manager;
    }

    /**
     * @param FileManager $manager
     */
    public function setFileManager(FileManager $manager)
    {
        $this->fileManager = $manager;
    }

    /**
     * @param ConfigFileValidator $validator
     */
    public function setValidator(ConfigFileValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string|null $filesDir
     */
    public function setFilesDir(?string $filesDir): void
    {
        $this->filesDir = rtrim($filesDir, DIRECTORY_SEPARATOR);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return File::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return is_object($data) && $data instanceof File;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->createFileEntity($data['uri'] ?? '', $data['uuid'] ?? '');
    }

    /**
     * {@inheritdoc}
     *
     * @param File $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $fileUrl = null;
        // It is impossible to generate URL for a file without ID.
        if ($object->getId()) {
            $fileUrl = $this->attachmentManager->getFileUrl(
                $object,
                FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        return [
            'uuid' => $object->getUuid(),
            'uri' => $fileUrl,
        ];
    }

    /**
     * Creates file entity with non-fetched file that can be fetched later during import.
     *
     * @param string $uri
     * @param string $uuid
     *
     * @return File
     */
    private function createFileEntity(string $uri, string $uuid): File
    {
        $file = new File();
        $file->setUuid($uuid ?: UUIDGenerator::v4());
        if ($uri) {
            // Sets SymfonyFile without checking path as anyway the file must not be uploaded in normalizer, so it
            // should pass through any file.
            $file->setFile(new SymfonyFile($this->denormalizeFileUri($uri), false));
        }

        return $file;
    }

    /**
     * If the provided uri is relative path, then makes it absolute by prepending files directory path.
     *
     * @param string $uri
     *
     * @return string
     */
    private function denormalizeFileUri(string $uri): string
    {
        $filesystem = new SymfonyFileSystem();
        if (!$filesystem->isAbsolutePath($uri)) {
            $uri = sprintf('%s%s%s', $this->filesDir, DIRECTORY_SEPARATOR, ltrim($uri, DIRECTORY_SEPARATOR));
        }

        return $uri;
    }
}
