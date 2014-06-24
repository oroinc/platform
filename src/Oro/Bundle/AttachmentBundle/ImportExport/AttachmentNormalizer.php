<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;

class AttachmentNormalizer implements DenormalizerInterface, NormalizerInterface
{
    /** @var  AttachmentManager */
    protected $attachmentManager;

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
        return $type == 'Oro\Bundle\AttachmentBundle\Entity\Attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return is_object($data) && $data instanceof Attachment;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $result = null;
        $entity =  $this->attachmentManager->prepareRemoteFile($data);
        $violations = $this->validator->validate($context['entityName'], $context['fieldName'], $entity);

        if (!$violations->count()) {
            $this->attachmentManager->upload($entity);
            $result = $entity;
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
            $context['field']['name'],
            $object,
            'download',
            true
        );
    }
}
