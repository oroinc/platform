<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listens to onProcessBefore and onProcessAfter events of import strategy to handle file importing.
 */
class FileStrategyEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var FileManager */
    private $fileManager;

    /** @var FieldHelper */
    private $fieldHelper;

    /** @var DatabaseHelper */
    private $databaseHelper;

    /** @var ImportStrategyHelper */
    private $importStrategyHelper;

    /** @var FileImportStrategyHelper */
    private $fileImportStrategyHelper;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var TranslatorInterface */
    private $translator;

    /** @var File[] */
    private $scheduledForDeletion = [];

    /** @var SymfonyFile[] */
    private $scheduledForUpload = [];

    public function __construct(
        FileManager $fileManager,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        ImportStrategyHelper $importStrategyHelper,
        FileImportStrategyHelper $fileImportStrategyHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator
    ) {
        $this->fileManager = $fileManager;
        $this->fieldHelper = $fieldHelper;
        $this->databaseHelper = $databaseHelper;
        $this->importStrategyHelper = $importStrategyHelper;
        $this->fileImportStrategyHelper = $fileImportStrategyHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
        $this->logger = new NullLogger();
    }

    public function onProcessBefore(StrategyEvent $event): void
    {
        $entity = $event->getEntity();
        $itemData = (array)($event->getContext()->getValue('itemData') ?? []);

        $this->processRelations(
            $entity,
            $itemData,
            function (object $entity, array $relation, array $itemData) {
                if (is_a($relation['related_entity_name'], File::class, true)) {
                    $this->beforeProcessFileField($entity, $relation['name'], $itemData);
                }

                if (is_a($relation['related_entity_name'], FileItem::class, true)) {
                    $this->beforeProcessMultiFileField($entity, $relation['name']);
                }
            }
        );
    }

    private function processRelations(object $entity, array $itemData, callable $callback): void
    {
        $entityClass = $this->fileImportStrategyHelper->getClass($entity);
        $relations = $this->getRelations($entityClass);
        foreach ($relations as $relation) {
            if ($this->fieldHelper->isFieldExcluded($entityClass, $relation['name'], $itemData)) {
                continue;
            }

            $callback($entity, $relation, $itemData);
        }
    }

    private function getRelations(string $entityClass): array
    {
        return $this->fieldHelper->getRelations(
            $entityClass,
            false,
            true,
            false
        );
    }

    private function beforeProcessFileField(object $entity, string $fieldName, array $itemData): void
    {
        /** @var File $file */
        $file = $this->fieldHelper->getObjectValue($entity, $fieldName);
        if (!$file) {
            // Checks if file column is specified in import file.
            if (array_key_exists($fieldName, $itemData)) {
                $existingFile = $this->fileImportStrategyHelper->getFromExistingEntity($entity, $fieldName);
                // Schedules existing file for deletion.
                if ($existingFile) {
                    $existingEntity = $this->databaseHelper->findOneByIdentity($entity);
                    $existingEntityId = $this->databaseHelper->getIdentifier($existingEntity);
                    $this->scheduledForDeletion[$existingEntityId][$fieldName] = $existingFile;
                }
            }
        } else {
            $existingFile = $this->fileImportStrategyHelper->getFromExistingEntity($entity, $fieldName);
            $this->processFile($file, $existingFile);
        }
    }

    private function processFile(File $file, ?File $existingFile): void
    {
        if ($existingFile) {
            // Sets id of existing file to prevent creation of new entity.
            $this->fieldHelper->setObjectValue($file, 'id', $existingFile->getId());

            $file->setUuid($existingFile->getUuid());
        } else {
            // Sets new UUID as we cannot create new file entity with already existing UUID.
            $file->setUuid(UUIDGenerator::v4());
        }

        // Saves SymfonyFile entities prepared for uploading to prevent loosing them later during import strategy.
        $this->scheduledForUpload[$file->getUuid()] = $file->getFile();
    }

    private function beforeProcessMultiFileField(object $entity, string $fieldName): void
    {
        /** @var FileItem[]|Collection $fileItems */
        $fileItems = $this->fieldHelper->getObjectValue($entity, $fieldName);
        /** @var FileItem[]|Collection $existingFileItems */
        $existingFileItems = $this->fileImportStrategyHelper
            ->getFromExistingEntity($entity, $fieldName, new ArrayCollection());

        foreach ($fileItems as $index => $fileItem) {
            $file = $fileItem->getFile();
            if (!$file) {
                continue;
            }

            $fileItemSortOrder = $fileItem->getSortOrder();
            /** @var FileItem $existingFileItem */
            $existingFileItem = $existingFileItems->get($index);
            $existingFile = null;

            if ($existingFileItem) {
                // Sets id of existing FileItem to prevent creation of new entity.
                $this->fieldHelper->setObjectValue($fileItem, 'id', $existingFileItem->getId());

                if (!$fileItemSortOrder) {
                    $fileItemSortOrder = $existingFileItem->getSortOrder();

                    // Sets sort order of existing FileItem if it has not been set in the import file.
                    $fileItem->setSortOrder($fileItemSortOrder);
                }

                $existingFile = $existingFileItem->getFile();
            }

            $this->processFile($file, $existingFile);
        }

        $this->fillSortOrderFields($fileItems);
    }

    /**
     * @param FileItem[]|Collection $fileItems
     */
    private function fillSortOrderFields(Collection $fileItems): void
    {
        $maxSortOrder = 0;
        $newFileItemsWithoutSortOrder = [];
        foreach ($fileItems as $fileItem) {
            $fileItemSortOrder = $fileItem->getSortOrder();

            if ($fileItemSortOrder > $maxSortOrder) {
                $maxSortOrder = $fileItemSortOrder;
                continue;
            }

            if (!$fileItemSortOrder) {
                $newFileItemsWithoutSortOrder[] = $fileItem;
            }
        }

        foreach ($newFileItemsWithoutSortOrder as $newFileItem) {
            $newFileItem->setSortOrder(++$maxSortOrder);
        }
    }

    public function onProcessAfter(StrategyEvent $event): void
    {
        $errors = [[]];
        $entity = $event->getEntity();
        $itemData = (array)($event->getContext()->getValue('itemData') ?? []);

        $this->processRelations(
            $entity,
            $itemData,
            function (object $entity, array $relation, array $itemData) use (&$errors) {
                if (is_a($relation['related_entity_name'], File::class, true)) {
                    $errors[] = $this->afterProcessFileField($entity, $relation['name'], $itemData);
                }

                if (is_a($relation['related_entity_name'], FileItem::class, true)) {
                    $errors[] = $this->afterProcessMultiFileField($entity, $relation['name'], $itemData);
                }
            }
        );

        if ($errors = array_merge(...$errors)) {
            // File importing has failed, entity will be skipped from import.
            $event->setEntity(null);
            $event->stopPropagation();

            $errors[] = $this->translator->trans('oro.attachment.import.entity_is_skipped');

            $this->importStrategyHelper->addValidationErrors($errors, $event->getContext());
        }
    }

    private function afterProcessFileField(object $entity, string $fieldName, array $itemData): array
    {
        $errors = [];

        /** @var File $file */
        $file = $this->fieldHelper->getObjectValue($entity, $fieldName);
        if (!$file) {
            $entityId = $this->databaseHelper->getIdentifier($entity);
            $fileScheduledForDeletion = $this->scheduledForDeletion[$entityId][$fieldName] ?? null;
            if ($fileScheduledForDeletion) {
                // Flag a file for deletion.
                $fileScheduledForDeletion->setEmptyFile(true);
                // Required to mark entity as dirty so file deletion listeners can hook in.
                $fileScheduledForDeletion->preUpdate();

                unset($this->scheduledForDeletion[$entityId]);
            }
        } else {
            $originUuid = $this->fieldHelper->getItemData($itemData, $fieldName)['uuid'] ?? '';
            $errors = $this->processUploadAndValidate($file, $originUuid, $entity, $fieldName);
        }

        return $errors;
    }

    private function processUploadAndValidate(
        File $file,
        string $originUuid,
        object $entity,
        string $fieldName,
        ?int $index = null
    ): array {
        $errors = [[]];

        if (!$file->getId() || $file->getUuid() !== $originUuid) {
            $uploadOrCloneErrors = $this->uploadOrCloneFromOrigin($file, $fieldName, $originUuid);
            $errors[] = $uploadOrCloneErrors;

            // Skips validation if file failed to upload or clone.
            if (!$uploadOrCloneErrors) {
                $errors[] = $this->fileImportStrategyHelper->validateSingleFile($file, $entity, $fieldName, $index);
            }
        }

        $errors = array_merge(...$errors);
        if ($errors && $file->getId()) {
            $this->databaseHelper->refreshEntity($file);
        }

        return $errors;
    }

    /**
     * @param File $file
     * @param string $fieldName
     * @param string $originUuid
     *
     * @return string[]
     */
    private function uploadOrCloneFromOrigin(File $file, string $fieldName, string $originUuid): array
    {
        $originFile = $this->fileImportStrategyHelper->findFileByUuid($originUuid);
        if ($originFile) {
            $errors = $this->cloneFromOriginFile($file, $originFile);
        } else {
            $nonFetchedFile = $this->scheduledForUpload[$file->getUuid()] ?? null;
            if ($nonFetchedFile) {
                $errors = $this->uploadFromNonFetchedFile($file, $nonFetchedFile);
            } else {
                $errors = [
                    $this->translator->trans(
                        'oro.attachment.import.failed_to_upload_or_clone',
                        ['%fieldname%' => $fieldName]
                    ),
                ];
            }
        }

        return $errors;
    }

    /**
     * Uploads non-fetched file of the given File entity.
     *
     * @param File $file
     * @param SymfonyFile $nonFetchedFile
     *
     * @return string[]
     */
    private function uploadFromNonFetchedFile(File $file, SymfonyFile $nonFetchedFile): array
    {
        $errors = [];
        try {
            $this->fileManager->setFileFromPath($file, $nonFetchedFile->getPathname());
            $file->preUpdate();
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to upload a file during import', ['e' => $exception]);

            $errors = [
                $this->translator->trans(
                    'oro.attachment.import.failed_to_upload',
                    ['%path%' => $nonFetchedFile->getPathname(), '%error%' => $exception->getMessage()]
                ),
            ];
        }

        return $errors;
    }

    /**
     * @param File $file
     * @param File $originFile
     *
     * @return string[]
     */
    private function cloneFromOriginFile(File $file, File $originFile): array
    {
        $errors = [];
        $parameters = ['%origin_id%' => $originFile->getId(), '%origin_uuid%' => $originFile->getUuid()];
        $clonedFile = null;

        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $originFile)) {
            $parameters['%error%'] = $this->translator
                ->trans('oro.attachment.import.failed_to_clone_forbidden', $parameters);
        } else {
            try {
                $symfonyFile = $this->fileManager->getFileFromFileEntity($originFile);

                // SymfonyFile which is set here will be processed later by oro_attachment.listener.file_listener.
                $file->setFile($symfonyFile);
                $file->setOriginalFilename($originFile->getOriginalFilename());
                $file->preUpdate();
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to clone a file during import', ['e' => $exception]);
                $parameters['%error%'] = $exception->getMessage();
            }
        }

        if (!$file->getFile()) {
            $errors[] = $this->translator->trans(
                'oro.attachment.import.failed_to_clone',
                $parameters + [
                    '%error%' => $this->translator->trans('oro.attachment.import.failed_to_clone_origin_file_empty'),
                ]
            );
        }

        return $errors;
    }

    private function afterProcessMultiFileField(object $entity, string $fieldName, array $itemData): array
    {
        $errors = [[]];
        /** @var FileItem[]|Collection $fileItems */
        $fileItems = $this->fieldHelper->getObjectValue($entity, $fieldName);
        $collectionItemData = $this->fieldHelper->getItemData($itemData, $fieldName);

        $inverseRelationFieldName = $this->databaseHelper->getInversedRelationFieldName(
            $this->fileImportStrategyHelper->getClass($entity),
            $fieldName
        );

        foreach ($fileItems as $i => $fileItem) {
            // Replaces not managed object stored in inverse relation with the already existing one.
            $this->fieldHelper->setObjectValue($fileItem, $inverseRelationFieldName, $entity);

            if ($file = $fileItem->getFile()) {
                $originUuid = $this->fieldHelper->getItemData(array_shift($collectionItemData))['file']['uuid'] ?? '';
                $errors[] = $this->processUploadAndValidate($file, $originUuid, $entity, $fieldName, $i);
            }
        }

        $errors[] = $this->fileImportStrategyHelper->validateFileCollection($fileItems, $entity, $fieldName);

        return array_merge(...$errors);
    }
}
