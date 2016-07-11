<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

/**
 * Set attachment template to attachment associations
 */
class MergeListener
{
    const TEMPLATE_NAME = 'OroAttachmentBundle:Form:mergeValue.html.twig';

    /** @var AttachmentManager $attachmentManager */
    private $attachmentManager;

    /**
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(AttachmentManager $attachmentManager)
    {
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * @param EntityMetadataEvent $event
     */
    public function onBuildMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();
        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $fieldName = $this->getAttachmentFieldName($entityMetadata);

        if (null === $fieldName) {
            return;
        }

        $fieldName = str_replace('\\', '_', Attachment::class) . '_' . $fieldName;

        $fieldMetadata = new FieldMetadata(['field_name' => $fieldName]);
        if (isset($fieldsMetadata[$fieldName])) {
            $fieldMetadata = $fieldsMetadata[$fieldName];
        }

        if (!$fieldMetadata->has('template')) {
            $fieldMetadata->set('template', self::TEMPLATE_NAME);
        }

        $entityMetadata->addFieldMetadata($fieldMetadata);
    }

    /**
     * @param EntityMetadata $entityMetadata
     *
     * @return string|null
     */
    protected function getAttachmentFieldName(EntityMetadata $entityMetadata)
    {
        $className = $entityMetadata->getClassName();
        $targets = $this->attachmentManager->getAttachmentTargets();

        if (isset($targets[$className])) {
            return $targets[$className];
        }

        return null;
    }
}
