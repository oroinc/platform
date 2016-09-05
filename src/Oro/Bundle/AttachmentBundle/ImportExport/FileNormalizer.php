<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;

class FileNormalizer implements DenormalizerInterface, NormalizerInterface
{
    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var FileManager */
    protected $fileManager;

    /** @var ConfigFileValidator */
    protected $validator;

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
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return $type == 'Oro\Bundle\AttachmentBundle\Entity\File';
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
        $entity = $this->fileManager->createFileEntity($data);
        if ($entity) {
            $violations = $this->validator->validate(
                $entity->getFile(),
                $context['entityName'],
                $context['fieldName']
            );
            if (!$violations->count()) {
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
}
