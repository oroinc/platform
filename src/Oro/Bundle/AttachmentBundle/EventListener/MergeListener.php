<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

/**
 * Set attachment template to attachment associations
 */
class MergeListener
{
    const TEMPLATE_NAME = 'OroAttachmentBundle:Form:mergeValue.html.twig';

    /** @var AttachmentManager $attachmentManager */
    protected $attachmentManager;

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
        $fieldName = $this->getAttachmentFieldName($entityMetadata);

        if (null === $fieldName) {
            return;
        }

        $fieldMetadata = $this->getFieldMetadata($entityMetadata, $fieldName);

        $mergeModes = [MergeModes::UNITE, MergeModes::REPLACE];
        if ($fieldMetadata->has('merge_modes')) {
            $mergeModes = array_merge($mergeModes, (array) $fieldMetadata->get('merge_modes'));
        }

        $fieldMetadata->set('merge_modes', $mergeModes);

        if (!$fieldMetadata->has('template')) {
            $fieldMetadata->set('template', self::TEMPLATE_NAME);
        }
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

        if (!isset($targets[$className])) {
            return null;
        }

        return str_replace('\\', '_', Attachment::class) . '_' . $targets[$className];
    }

    protected function getFieldMetadata(EntityMetadata $entityMetadata, $fieldName)
    {
        $fieldsMetadata = $entityMetadata->getFieldsMetadata();

        if (isset($fieldsMetadata[$fieldName])) {
            return $fieldsMetadata[$fieldName];
        }

        $fieldMetadata = new FieldMetadata(['field_name' => $fieldName]);
        $entityMetadata->addFieldMetadata($fieldMetadata);

        return $fieldMetadata;
    }
}
