<?php

namespace Oro\Bundle\DigitalAssetBundle\ImportExport\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listens to onProcessAfter event of import strategy to handle digital assets during file importing.
 * This listener must work in pair with and be called after the
 * Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileStrategyEventListener
 */
class DigitalAssetAwareFileStrategyEventListener
{
    /** @var AttachmentEntityConfigProviderInterface */
    private $attachmentEntityConfigProvider;

    /** @var FieldHelper */
    private $fieldHelper;

    /** @var DatabaseHelper */
    private $databaseHelper;

    /** @var ImportStrategyHelper */
    private $importStrategyHelper;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TranslatorInterface */
    private $translator;

    /** @var DigitalAsset[] */
    private $newDigitalAssets = [];

    /** @var File[] */
    private $filesWithDigitalAssetsToPersist = [];

    /** @var File[] */
    private $newFiles;

    public function __construct(
        AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        ImportStrategyHelper $importStrategyHelper,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->databaseHelper = $databaseHelper;
        $this->fieldHelper = $fieldHelper;
        $this->attachmentEntityConfigProvider = $attachmentEntityConfigProvider;
        $this->importStrategyHelper = $importStrategyHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    public function onProcessAfter(StrategyEvent $event): void
    {
        $errors = [[]];
        $entity = $event->getEntity();
        $entityClass = $this->doctrineHelper->getClass($entity);
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

        if ($errors = array_merge(...$errors)) {
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
            ->getFieldConfig($this->doctrineHelper->getClass($entity), $fieldName);

        return $attachmentConfig && $attachmentConfig->is('use_dam');
    }

    /**
     * @param object $entity
     * @param string $fieldName
     * @param array $itemData
     *
     * @return string[]
     */
    private function afterProcessFileField(object $entity, string $fieldName, array $itemData): array
    {
        $errors = [];

        /** @var File $file */
        $file = $this->fieldHelper->getObjectValue($entity, $fieldName);

        if ($file) {
            $originUuid = $this->fieldHelper->getItemData($itemData, $fieldName)['uuid'] ?? '';

            if ($this->isDamEnabled($entity, $fieldName)) {
                $errors = $this->createOrReuseDigitalAsset($file, $originUuid);
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
     *
     * @return string[]
     */
    private function createOrReuseDigitalAsset(File $file, string $originUuid): array
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
            $this->filesWithDigitalAssetsToPersist[] = $file;

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
        return $this->newFiles[$uuid] ??
            ($uuid ? $this->databaseHelper->findOneBy(File::class, ['uuid' => $uuid]) : null);
    }

    /**
     * @param object $entity
     * @param string $fieldName
     * @param array $itemData
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

        foreach ($fileItems as $fileItem) {
            $file = $fileItem->getFile();
            if ($file) {
                $originUuid = $this->fieldHelper->getItemData(array_shift($fieldItemData))['file']['uuid'] ?? '';

                if ($isDamEnabled) {
                    $errors[] = $this->createOrReuseDigitalAsset($file, $originUuid);
                }

                if (!isset($this->newFiles[$file->getUuid()]) && !$file->getId()) {
                    $this->newFiles[$file->getUuid()] = $file;
                }
            }
        }

        return array_merge(...$errors);
    }

    public function preFlush(PreFlushEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();

        foreach ($this->filesWithDigitalAssetsToPersist as $file) {
            if (!$entityManager->contains($file)) {
                // Skips files that are not going to be persisted.
                continue;
            }

            // Prevents uploading as the file will be reflected from digital asset.
            $file->setFile(null);

            $digitalAsset = $file->getDigitalAsset();
            if ($digitalAsset) {
                $entityManager->persist($digitalAsset);
            }
        }

        $this->filesWithDigitalAssetsToPersist = [];
    }
}
