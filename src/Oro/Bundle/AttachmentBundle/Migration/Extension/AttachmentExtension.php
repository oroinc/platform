<?php

namespace Oro\Bundle\AttachmentBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides an ability to create file and attachment fields and attachment association.
 */
class AttachmentExtension implements ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    const FILE_TABLE_NAME       = 'oro_attachment_file';
    const ATTACHMENT_TABLE_NAME = 'oro_attachment';
    const ATTACHMENT_FILE_ITEM_TABLE = 'oro_attachment_file_item';

    /**
     * @param Schema $schema
     * @param string $sourceTable      Target entity table name
     * @param string $sourceColumnName A column name is used to show related entity
     * @param array  $options          Additional options for relation
     * @param int    $maxFileSize      Max allowed file size in megabytes
     */
    public function addFileRelation(
        Schema $schema,
        $sourceTable,
        $sourceColumnName,
        $options = [],
        $maxFileSize = 1
    ) {
        $entityTable = $schema->getTable($sourceTable);

        $options['attachment']['maxsize'] = $maxFileSize;

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $entityTable,
            $sourceColumnName,
            self::FILE_TABLE_NAME,
            'id',
            $options,
            'file'
        );
    }

    /**
     * @param Schema $schema
     * @param string $sourceTable      Target entity table name
     * @param string $sourceColumnName A column name is used to show related entity
     * @param array  $options          Additional options for relation
     * @param int    $maxFileSize      Max allowed file size in megabytes
     */
    public function addMultiFileRelation(
        Schema $schema,
        string $sourceTable,
        string $sourceColumnName,
        array $options = [],
        $maxFileSize = 1
    ): void {
        $table = $schema->getTable($sourceTable);
        $fileItemTable = $schema->getTable(self::ATTACHMENT_FILE_ITEM_TABLE);

        $options['attachment']['maxsize'] = $maxFileSize;

        $this->extendExtension->addOneToManyRelation(
            $schema,
            $table,
            $sourceColumnName,
            $fileItemTable,
            ['id'],
            ['id'],
            ['id'],
            $options,
            FieldConfigHelper::MULTI_FILE_TYPE
        );
    }

    /**
     * @param Schema   $schema
     * @param string   $sourceTable      Target entity table name
     * @param string   $sourceColumnName A column name is used to show related entity
     * @param array    $options          Additional options for relation
     * @param int      $maxFileSize      Max allowed file size in megabytes
     * @param int      $thumbWidth       Thumbnail width in pixels
     * @param int      $thumbHeight      Thumbnail height in pixels
     * @param array    $mimeTypes        The list of allowed MIME types
     */
    public function addImageRelation(
        Schema $schema,
        $sourceTable,
        $sourceColumnName,
        $options = [],
        $maxFileSize = 1,
        $thumbWidth = 32,
        $thumbHeight = 32,
        array $mimeTypes = []
    ) {
        $entityTable = $schema->getTable($sourceTable);

        $options['attachment']['maxsize'] = $maxFileSize;
        $options['attachment']['width'] = $thumbWidth;
        $options['attachment']['height'] = $thumbHeight;
        $options['attachment']['mimetypes'] = MimeTypesConverter::convertToString($mimeTypes);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $entityTable,
            $sourceColumnName,
            self::FILE_TABLE_NAME,
            'id',
            $options,
            'image'
        );
    }

    /**
     * @param Schema   $schema
     * @param string   $sourceTable      Target entity table name
     * @param string   $sourceColumnName A column name is used to show related entity
     * @param array    $options          Additional options for relation
     * @param int      $maxFileSize      Max allowed file size in megabytes
     * @param int      $thumbWidth       Thumbnail width in pixels
     * @param int      $thumbHeight      Thumbnail height in pixels
     * @param array    $mimeTypes        The list of allowed MIME types
     */
    public function addMultiImageRelation(
        Schema $schema,
        string $sourceTable,
        string $sourceColumnName,
        array $options = [],
        int $maxFileSize = 1,
        int $thumbWidth = 32,
        int $thumbHeight = 32,
        array $mimeTypes = []
    ): void {
        $table = $schema->getTable($sourceTable);
        $fileItemTable = $schema->getTable(self::ATTACHMENT_FILE_ITEM_TABLE);

        $options['attachment']['maxsize'] = $maxFileSize;
        $options['attachment']['width'] = $thumbWidth;
        $options['attachment']['height'] = $thumbHeight;
        $options['attachment']['mimetypes'] = MimeTypesConverter::convertToString($mimeTypes);

        $this->extendExtension->addOneToManyRelation(
            $schema,
            $table,
            $sourceColumnName,
            $fileItemTable,
            ['id'],
            ['id'],
            ['id'],
            $options,
            FieldConfigHelper::MULTI_IMAGE_TYPE
        );
    }

    /**
     * Adds the association between the target table and the attachment table
     *
     * @param Schema   $schema
     * @param string   $targetTableName  Target entity table name
     * @param string[] $allowedMimeTypes The list of allowed MIME types
     * @param int      $maxFileSize      Max allowed file size in megabytes
     */
    public function addAttachmentAssociation(
        Schema $schema,
        $targetTableName,
        array $allowedMimeTypes = [],
        $maxFileSize = 1
    ) {
        $attachmentTable = $schema->getTable(self::ATTACHMENT_TABLE_NAME);
        $targetTable     = $schema->getTable($targetTableName);

        $primaryKeyColumns = $targetTable->getPrimaryKeyColumns();
        $targetColumnName  = array_shift($primaryKeyColumns);

        $options = new OroOptions();
        $options->set('attachment', 'enabled', true);
        $options->set('attachment', 'maxsize', $maxFileSize);
        $options->set('attachment', 'mimetypes', MimeTypesConverter::convertToString($allowedMimeTypes));
        $targetTable->addOption(OroOptions::KEY, $options);

        $associationName = ExtendHelper::buildAssociationName(
            $this->extendExtension->getEntityClassByTableName($targetTableName)
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $attachmentTable,
            $associationName,
            $targetTable,
            $targetColumnName
        );
    }
}
