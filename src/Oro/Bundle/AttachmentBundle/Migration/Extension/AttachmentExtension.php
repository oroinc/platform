<?php

namespace Oro\Bundle\AttachmentBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;

class AttachmentExtension implements ExtendExtensionAwareInterface
{
    const ATTACHMENT_TABLE_NAME = 'oro_attachment';

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var ExtendOptionsManager */
    protected $extendOptionsManager;

    /**
     * @param ExtendOptionsManager $extendOptionsManager
     */
    public function __construct(ExtendOptionsManager $extendOptionsManager)
    {
        $this->extendOptionsManager = $extendOptionsManager;
    }

    /**
     * @param Schema $schema
     * @param string $sourceTable           Target entity table name
     * @param string $sourceColumnName      A column name is used to show related entity
     * @param string $type                  attachment OR attachmentImage
     * @param array  $options               Additional options for relation
     * @param int    $attachmentMaxSize     Max allowed file size in MB
     * @param int    $attachmentThumbWidth  Thumbnail width in PX (used in viewAction)
     * @param int    $attachmentThumbHeight Thumbnail height in PX (used in viewAction)
     */
    public function addAttachmentRelation(
        Schema $schema,
        $sourceTable,
        $sourceColumnName,
        $type,
        $options = [],
        $attachmentMaxSize = 1,
        $attachmentThumbWidth = 32,
        $attachmentThumbHeight = 32
    ) {
        $entityTable = $schema->getTable($sourceTable);

        $attachmentScopeOptions = [
            'maxsize' => $attachmentMaxSize
        ];

        if ($type == AttachmentScope::ATTACHMENT_IMAGE) {
            $attachmentScopeOptions['width']  = $attachmentThumbWidth;
            $attachmentScopeOptions['height'] = $attachmentThumbHeight;
        }

        $relationOptions = [
            'extend' => [
                'is_extend' => true
            ],
            'attachment' => $attachmentScopeOptions
        ];

        if (!empty($options)) {
            $relationOptions = array_merge($relationOptions, $options);
        }

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $entityTable,
            $sourceColumnName,
            self::ATTACHMENT_TABLE_NAME,
            'id'
        );

        $this->extendOptionsManager->setColumnType($sourceTable, $sourceColumnName, $type);
        $this->extendOptionsManager->setColumnOptions($sourceTable, $sourceColumnName, $relationOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }
}
