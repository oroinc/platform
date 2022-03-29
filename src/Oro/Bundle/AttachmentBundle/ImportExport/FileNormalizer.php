<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * The normalizer for attached files.
 */
class FileNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    private AttachmentManager $attachmentManager;

    private FileManager $fileManager;

    private AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider;

    public function __construct(
        AttachmentManager $attachmentManager,
        FileManager $fileManager,
        AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->fileManager = $fileManager;
        $this->attachmentEntityConfigProvider = $attachmentEntityConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return File::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof File;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return $this->createFileEntity(
            $data['uri'] ?? '',
            $data['uuid'] ?? '',
            $this->isFileStoredExternally($context['entityName'] ?? '', $context['originalFieldName'] ?? '')
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param File $object
     */
    public function normalize($object, string $format = null, array $context = [])
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
     */
    private function createFileEntity(string $uri, string $uuid, bool $isExternalUrl): File
    {
        $file = new File();
        $file->setUuid($uuid ?: UUIDGenerator::v4());
        if ($uri) {
            if ($isExternalUrl) {
                // Sets ExternalFile without any checks as anyway the external file must not be accessed
                // in normalizer, so it should pass through any file.
                $file->setFile(new ExternalFile($uri));
            } else {
                if ($this->isRelativePath($uri)) {
                    $uri = $this->fileManager->getReadonlyFilePath($uri);
                }
                // Sets SymfonyFile without checking path at constructor as anyway
                // the file must not be uploaded in normalizer, so it should pass through any file.
                $file->setFile(new SymfonyFile($uri, false));
            }
        }

        return $file;
    }

    private function isRelativePath(string $path): bool
    {
        return
            !str_contains($path, '://')
            && !is_file($path);
    }

    private function isFileStoredExternally(string $entityClass, string $fieldName): bool
    {
        if (!$entityClass || !$fieldName) {
            return false;
        }

        $isFileStoredExternally = false;
        $entityFieldConfig = $this->attachmentEntityConfigProvider->getFieldConfig($entityClass, $fieldName);
        if ($entityFieldConfig && $entityFieldConfig->has('is_stored_externally')) {
            $isFileStoredExternally = $entityFieldConfig->get('is_stored_externally');
        }

        return $isFileStoredExternally;
    }
}
