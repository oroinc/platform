<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\AttachmentBundle\Validator\ConfigMultipleFileValidator;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Helper for FileStrategyEventListener which handles file importing.
 */
class FileImportStrategyHelper
{
    /** @var FieldHelper */
    private $fieldHelper;

    /** @var DatabaseHelper */
    private $databaseHelper;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigFileValidator */
    private $configFileValidator;

    /** @var ConfigMultipleFileValidator */
    private $configMultipleFileValidator;

    /** @var TranslatorInterface */
    private $translator;

    /** @var array */
    private $fieldLabels = [];

    public function __construct(
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        DoctrineHelper $doctrineHelper,
        ConfigFileValidator $configFileValidator,
        ConfigMultipleFileValidator $configMultipleFileValidator,
        TranslatorInterface $translator
    ) {
        $this->fieldHelper = $fieldHelper;
        $this->databaseHelper = $databaseHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->configFileValidator = $configFileValidator;
        $this->configMultipleFileValidator = $configMultipleFileValidator;
        $this->translator = $translator;
    }

    /**
     * @param File $file
     * @param object $entity
     * @param string $fieldName
     * @param int|null $index
     *
     * @return string[]
     */
    public function validateSingleFile(File $file, object $entity, string $fieldName, ?int $index = null): array
    {
        $violations = [];
        $symfonyFile = $file->getFile();
        if ($symfonyFile) {
            $entityClass = $this->getClass($entity);

            $constraintsViolationList = $this->configFileValidator->validate($symfonyFile, $entityClass, $fieldName);

            $messageParameters = [
                '%fieldname%' => $this->getFieldLabel($entityClass, $fieldName) ?: $fieldName,
                '%path%' => $symfonyFile->getPathname(),
            ];

            if ($index !== null) {
                $messageKey = 'oro.attachment.import.multi_file_constraint_violation';
                $messageParameters['%index%'] = $index + 1;
            } else {
                $messageKey = 'oro.attachment.import.file_constraint_violation';
            }

            $violations = $this
                ->getPlainErrorsFromViolationList($constraintsViolationList, $messageKey, $messageParameters);
        }

        return $violations;
    }

    private function getPlainErrorsFromViolationList(
        ConstraintViolationListInterface $constraintsViolationList,
        string $messageKey,
        array $messageParameters
    ): array {
        $violations = [];

        if ($constraintsViolationList->count()) {
            foreach ($constraintsViolationList as $violation) {
                $messageParameters['%error%'] = $violation->getMessage();
                $violations[] = $this->translator->trans($messageKey, $messageParameters);
            }
        }

        return $violations;
    }

    /**
     * @param object $entity
     * @param string $fieldName
     * @param mixed $default
     *
     * @return mixed
     */
    public function getFromExistingEntity(object $entity, string $fieldName, $default = null)
    {
        $existingEntity = $this->databaseHelper->findOneByIdentity($entity);
        if ($existingEntity) {
            $value = $this->fieldHelper->getObjectValue($existingEntity, $fieldName);
        }

        return $value ?? $default;
    }

    public function validateFileCollection(Collection $fileItems, object $entity, string $fieldName): array
    {
        $entityClass = $this->getClass($entity);
        $relations = $this->fieldHelper->getRelations($entityClass);
        if (!isset($relations[$fieldName])) {
            throw new \LogicException(sprintf('Field %s not found in entity %s', $fieldName, $entityClass));
        }

        $fieldType = $relations[$fieldName]['type'];
        if ($fieldType === 'multiFile') {
            $messageKey = 'oro.attachment.import.multi_file_field_constraint_violation';
            $constraintsViolationList = $this->configMultipleFileValidator
                ->validateFiles($fileItems, $entityClass, $fieldName);
        } elseif ($fieldType === 'multiImage') {
            $messageKey = 'oro.attachment.import.multi_image_field_constraint_violation';
            $constraintsViolationList = $this->configMultipleFileValidator
                ->validateImages($fileItems, $entityClass, $fieldName);
        } else {
            throw new \LogicException(
                sprintf(
                    'Cannot validate unsupported field type %s of field %s in entity %s',
                    $fieldType,
                    $fieldName,
                    $entityClass
                )
            );
        }

        return $this->getPlainErrorsFromViolationList(
            $constraintsViolationList,
            $messageKey,
            ['%fieldname%' => $this->getFieldLabel($entityClass, $fieldName) ?: $fieldName]
        );
    }

    public function getClass(object $entity): string
    {
        return $this->doctrineHelper->getClass($entity);
    }

    public function findFileByUuid(string $uuid): ?File
    {
        return $uuid ? $this->databaseHelper->findOneBy(File::class, ['uuid' => $uuid]) : null;
    }

    private function getFieldLabel(string $className, string $fieldName): ?string
    {
        if (!isset($this->fieldLabels[$className][$fieldName])) {
            $fields = $this->fieldHelper->getRelations($className);

            $this->fieldLabels[$className][$fieldName] = $fields[$fieldName]['label'] ?? null;
        }

        return $this->fieldLabels[$className][$fieldName];
    }
}
