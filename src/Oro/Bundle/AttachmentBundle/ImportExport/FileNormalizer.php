<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Validator\ConstraintViolationInterface;

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
        $result = null;
        $entity = $this->createFileEntity($data);
        if ($entity) {
            $violations = $this->validator->validate(
                $entity->getFile(),
                $context['entityName'],
                $context['fieldName']
            );
            if ($violations->count()) {
                /** @var ConstraintViolationInterface $violation */
                foreach ($violations as $violation) {
                    $this->logger->error(sprintf(
                        '%s. File: %s. Original File: %s.',
                        $violation->getMessage(),
                        $entity->getFile()->getPath(),
                        $data
                    ));
                }
            } else {
                $result = $entity;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->attachmentManager->getAttachment(
            $context['entityName'],
            $context['entityId'],
            $context['fieldName'],
            $object,
            'download',
            true
        );
    }

    /**
     * @param string $path
     *
     * @return File|null
     */
    private function createFileEntity($path)
    {
        try {
            return $this->fileManager->createFileEntity($path);
        } catch (IOException $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }
}
