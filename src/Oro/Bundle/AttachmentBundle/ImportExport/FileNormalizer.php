<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The normalizer for attached files.
 */
class FileNormalizer implements DenormalizerInterface, NormalizerInterface
{
    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var FileManager */
    private $fileManager;

    public function __construct(
        AttachmentManager $attachmentManager,
        FileManager $fileManager
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->fileManager = $fileManager;
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
        return $data instanceof File;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
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
            'uri'  => $fileUrl
        ];
    }

    /**
     * Creates file entity with non-fetched file that can be fetched later during import.
     */
    private function createFileEntity(string $uri, string $uuid): File
    {
        $file = new File();
        $file->setUuid($uuid ?: UUIDGenerator::v4());
        if ($uri) {
            if ($this->isRelativePath($uri)) {
                $uri = $this->fileManager->getReadonlyFilePath($uri);
            }
            // Sets SymfonyFile without checking path at constructor as anyway
            // the file must not be uploaded in normalizer, so it should pass through any file.
            $file->setFile(new SymfonyFile($uri, false));
        }

        return $file;
    }

    private function isRelativePath(string $path): bool
    {
        return
            false === strpos($path, '://')
            && !is_file($path);
    }
}
