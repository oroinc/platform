<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;



class AttachmentNormalizer implements DenormalizerInterface, NormalizerInterface
{
    /** @var  AttachmentManager */
    protected $attachmentManager;

    public function construct(AttachmentManager $manager)
    {
        $this->attachmentManager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return true;
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
        return '1';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        //todo: 1. refactor getAttachmentUrl method to support parent entity class and id without object
        //      2. in ConfigurableEntityNormalizer add parent entity id in context
        return $this->attachmentManager->getAttachmentUrl($context['entityName'], $context['fieldName'])
    }
}
