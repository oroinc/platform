<?php

namespace Oro\Bundle\DigitalAssetBundle\ImportExport\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listens to onProcessAfter event of import strategy to handle digital assets during file importing.
 * This listener must work in pair with and be called after the
 * {@see \Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileStrategyEventListener}.
 * The collected File entities are persisted by {@see DigitalAssetAwareFileStrategyPersistEventListener}.
 * This was done to avoid loading this listener and all services used by it when it is not required.
 */
class DigitalAssetAwareFileStrategyEventListener
{
    private AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider;

    private FieldHelper $fieldHelper;

    private DatabaseHelper $databaseHelper;

    private ImportStrategyHelper $importStrategyHelper;

    private FileImportStrategyHelper $fileImportStrategyHelper;

    private TranslatorInterface $translator;

    private MemoryCache $memoryCache;

    /** @var DigitalAsset[] */
    private array $newDigitalAssets = [];

    /** @var File[] */
    private array $newFiles = [];

    public function __construct(
        AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        ImportStrategyHelper $importStrategyHelper,
        FileImportStrategyHelper $fileImportStrategyHelper,
        TranslatorInterface $translator,
        MemoryCache $memoryCache
    ) {
        $this->databaseHelper = $databaseHelper;
        $this->fieldHelper = $fieldHelper;
        $this->attachmentEntityConfigProvider = $attachmentEntityConfigProvider;
        $this->importStrategyHelper = $importStrategyHelper;
        $this->fileImportStrategyHelper = $fileImportStrategyHelper;
        $this->translator = $translator;
        $this->memoryCache = $memoryCache;
    }

    public function onProcessAfter(StrategyEvent $event): void
    {
        $errors = [[]];
        $entity = $event->getEntity();
        $entityClass = $this->fileImportStrategyHelper->getClass($entity);
        $relations = $this->getRelations($entityClass);
        $itemData = (array)($event->getContext()->getValue('itemData') ?? []);

        foreach ($relations as $relation) {
            if ($this->fieldHelper->isFieldExcluded($entityClass, $relation['name'], $itemData)) {
                continue;
            }

            $relatedEntityName = $relation['related_entity_name'];

            if (is_a($relatedEntityName, File::class, true)) {
                $errors[] = $this->afterProcessFileField($entity, $relation['name'], $itemData);
            }

            if (is_a($relatedEntityName, FileItem::class, true)) {
                $errors[] = $this->afterProcessMultiFileField($entity, $relation['name'], $itemData);
            }
        }

        $errors = array_merge(...$errors);
        if ($errors) {
            // Digital asset importing has failed, entity will be skipped from import.
            $event->setEntity(null);
            $event->stopPropagation();

            $errors[] = $this->translator->trans('oro.digitalasset.import.entity_is_skipped');

            $this->importStrategyHelper->addValidationErrors($errors, $event->getContext());
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

    private function isDamEnabled(object $entity, string $fieldName): bool
    {
        $attachmentConfig = $this->attachmentEntityConfigProvider
            ->getFieldConfig($this->fileImportStrategyHelper->getClass($entity), $fieldName);

        return $attachmentConfig && $attachmentConfig->is('use_dam') && !$attachmentConfig->is('is_stored_externally');
    }

    /**
     * @param object $entity
     * @param string $fieldName
     * @param array  $itemData
     *
     * @return string[]
     */
    private function afterProcessFileField(object $entity, string $fieldName, array $itemData): array
    {
        $errors = [];

        /** @var File $file */
        $file = $this->fieldHelper->getObjectValue($entity, $fieldName);

        if ($file && !$file->getFile() instanceof ExternalFile) {
            $originUuid = $this->fieldHelper->getItemData($itemData, $fieldName)['uuid'] ?? '';

            if ($this->isDamEnabled($entity, $fieldName)) {
                $entityClass = $this->fileImportStrategyHelper->getClass($entity);
                $errors = $this->createOrReuseDigitalAsset(
                    $file,
                    $originUuid,
                    $this->fileImportStrategyHelper->getFieldLabel($entityClass, $fieldName)
                );
            }

            if (!isset($this->newFiles[$file->getUuid()]) && !$file->getId()) {
                $this->newFiles[$file->getUuid()] = $file;
            }
        }

        return $errors;
    }

    /**
     * @param File $file
     * @param string $originUuid
     * @param string $fieldLabel
     *
     * @return string[]
     */
    private function createOrReuseDigitalAsset(File $file, string $originUuid, string $fieldLabel): array
    {
        $errors = [];
        $digitalAsset = null;
        $foundByUuid = $this->findFileByUuid($originUuid);
        if ($foundByUuid) {
            if ($foundByUuid->getParentEntityClass() === DigitalAsset::class) {
                /** @var DigitalAsset $digitalAsset */
                $digitalAsset = $this->databaseHelper->find(DigitalAsset::class, $foundByUuid->getParentEntityId());
                if (!$digitalAsset) {
                    $errors[] = $this->translator->trans(
                        'oro.digitalasset.import.cannot_find_digital_asset',
                        [
                            '%file_id%' => $foundByUuid->getId(),
                            '%file_uuid%' => $originUuid,
                            '%digital_asset_id%' => $foundByUuid->getParentEntityId(),
                            '%fieldname%' => $fieldLabel,
                        ]
                    );
                }
            } else {
                $digitalAsset = $foundByUuid->getDigitalAsset();
            }
        }

        if (!$digitalAsset && !$errors) {
            $digitalAsset = $this->createDigitalAssetFromFile($file, $originUuid);
        }

        if ($digitalAsset) {
            $file->setDigitalAsset($digitalAsset);

            // Schedule to persist a new digital asset.
            $this->addFileWithDigitalAssetsToPersistList($file);

            if (!$foundByUuid && $originUuid) {
                // Sets origin UUID to the source file of new digital asset to prevent duplicating digital assets after
                // importing same UUIDs when the originally imported entities have been deleted, but digital assets are
                // still existing.
                $digitalAsset->getSourceFile()->setUuid($originUuid);
                $file->setUuid(UUIDGenerator::v4());
            }
        } else {
            $errors[] = $this->translator->trans(
                'oro.digitalasset.import.failed_to_create_or_reuse_digital_asset',
                [
                    '%file_uuid%' => $originUuid,
                    '%fieldname%' => $fieldLabel,
                ]
            );
        }

        return $errors;
    }

    private function createDigitalAssetFromFile(File $file, string $originUuid): ?DigitalAsset
    {
        if (!$file->getFile()) {
            return null;
        }

        $key = $originUuid ?: $file->getFile()->getPathname();

        if (!isset($this->newDigitalAssets[$key])) {
            $sourceFile = new File();
            $sourceFile->setOriginalFilename($file->getOriginalFilename());
            $sourceFile->setFile($file->getFile());

            $this->newDigitalAssets[$key] = (new DigitalAsset())
                ->addTitle((new LocalizedFallbackValue())->setString($file->getOriginalFilename()))
                ->setSourceFile($sourceFile);
        }

        return $this->newDigitalAssets[$key];
    }

    private function findFileByUuid(string $uuid): ?File
    {
        return $this->newFiles[$uuid] ?? $this->fileImportStrategyHelper->findFileByUuid($uuid);
    }

    /**
     * @param object $entity
     * @param string $fieldName
     * @param array  $itemData
     *
     * @return string[]
     */
    private function afterProcessMultiFileField(object $entity, string $fieldName, array $itemData): array
    {
        $errors = [[]];

        /** @var FileItem[]|Collection $fileItems */
        $fileItems = $this->fieldHelper->getObjectValue($entity, $fieldName);

        $fieldItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
        $isDamEnabled = $this->isDamEnabled($entity, $fieldName);
        if ($isDamEnabled) {
            $entityClass = $this->fileImportStrategyHelper->getClass($entity);
            $fieldLabel = $this->fileImportStrategyHelper->getFieldLabel($entityClass, $fieldName);
        }

        foreach ($fileItems as $fileItem) {
            $file = $fileItem->getFile();
            if ($file && !$file->getFile() instanceof ExternalFile) {
                $originUuid = $this->fieldHelper->getItemData(array_shift($fieldItemData))['file']['uuid'] ?? '';

                if ($isDamEnabled) {
                    $errors[] = $this->createOrReuseDigitalAsset($file, $originUuid, $fieldLabel);
                }

                if (!isset($this->newFiles[$file->getUuid()]) && !$file->getId()) {
                    $this->newFiles[$file->getUuid()] = $file;
                }
            }
        }

        return array_merge(...$errors);
    }

    private function addFileWithDigitalAssetsToPersistList(File $file): void
    {
        $key = DigitalAssetAwareFileStrategyPersistEventListener::FILES_WITH_DIGITAL_ASSETS_TO_PERSIST;
        $files = $this->memoryCache->get($key, []);
        $files[] = $file;
        $this->memoryCache->set($key, $files);
    }
}
