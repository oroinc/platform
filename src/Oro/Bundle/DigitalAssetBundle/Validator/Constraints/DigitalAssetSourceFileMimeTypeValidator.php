<?php

namespace Oro\Bundle\DigitalAssetBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if MIME type of source file cannot be changed to non-image if digital asset is already used in image fields.
 * Does not cover non-extended fields as it is impossible to distinguish file field from image field.
 */
class DigitalAssetSourceFileMimeTypeValidator extends ConstraintValidator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ConfigManager */
    private $entityConfigManager;

    /** @var MimeTypeChecker */
    private $mimeTypeChecker;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(
        ConfigManager $entityConfigManager,
        MimeTypeChecker $mimeTypeChecker,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityConfigManager = $entityConfigManager;
        $this->mimeTypeChecker = $mimeTypeChecker;
        $this->urlGenerator = $urlGenerator;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed|DigitalAsset $value
     * @param Constraint|DigitalAssetSourceFileMimeType $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        $this->assertArgumentsCorrect($value, $constraint);

        $uploadedFile = $this->getUploadedFile($value);
        if (!$uploadedFile) {
            return;
        }

        /** @var File $childFile */
        foreach ($this->getChildFiles($value) as $childFile) {
            $entityClass = $childFile->getParentEntityClass();
            $fieldName = $childFile->getParentEntityFieldName();
            if (!$entityClass || !$fieldName) {
                $this->logger->warning(sprintf('File #%s does not have parent entity data', $childFile->getId()));
                continue;
            }

            if (!$this->fieldTypeIsImage($entityClass, $fieldName)) {
                // Skips further checks as we cannot detect referenced field type without entity field config model or
                // referenced field type is not "image".
                continue;
            }

            $entityId = $childFile->getParentEntityId();
            $messageParams = [
                '%file_name%' => $uploadedFile instanceof UploadedFile
                    ? $uploadedFile->getClientOriginalName()
                    : $uploadedFile->getFilename(),
                '%field_name%' => $fieldName,
                '%entity_class%' => $entityClass,
                '%entity_id%' => $entityId,
            ];

            $entityMetadata = $this->entityConfigManager->getEntityMetadata($entityClass);
            // If entity has a configured view route, adds violation message with link.
            if ($entityMetadata && $entityMetadata->hasRoute('view', true)) {
                $viewRoute = $entityMetadata->getRoute('view');
                $entityUrl = $this->urlGenerator->generate($viewRoute, ['id' => $entityId]);
                $violationMessage = [
                    $constraint->mimeTypeCannotBeNonImageInEntity,
                    ['%entity_url%' => $entityUrl] + $messageParams,
                ];
            } else {
                // If entity does not have a configured view route, adds a plain violation message.
                $violationMessage = [$constraint->mimeTypeCannotBeNonImage, $messageParams];
            }

            $this->context
                ->buildViolation(...$violationMessage)
                ->atPath('sourceFile.file')
                ->addViolation();

            break;
        }
    }

    private function fieldTypeIsImage(string $entityClass, string $fieldName): bool
    {
        $fieldConfigModel = $this->entityConfigManager->getConfigFieldModel($entityClass, $fieldName);

        return $fieldConfigModel && $fieldConfigModel->getType() === 'image';
    }

    private function getChildFiles(DigitalAsset $digitalAsset): Collection
    {
        $childFiles = $digitalAsset->getChildFiles();
        if ($childFiles instanceof PersistentCollection) {
            $childFiles->initialize();
        }

        return $childFiles;
    }

    private function getUploadedFile(DigitalAsset $digitalAsset): ?ComponentFile
    {
        $sourceFile = $digitalAsset->getSourceFile();
        if (!$sourceFile) {
            return null;
        }

        $uploadedFile = $sourceFile->getFile();
        if (!$uploadedFile) {
            // Skips further checks as there is no newly uploaded file.
            return null;
        }

        $isImage = $this->mimeTypeChecker->isImageMimeType($uploadedFile->getMimeType());
        if ($isImage) {
            // Skips further checks because we validate only the case when non-image is being uploaded while
            // digital asset is used in image fields.
            return null;
        }

        return $uploadedFile;
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    private function assertArgumentsCorrect($value, Constraint $constraint): void
    {
        if (!$value instanceof DigitalAsset) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of %s, got %s',
                    DigitalAsset::class,
                    is_object($value) ? get_class($value) : $value
                )
            );
        }

        if (!$constraint instanceof DigitalAssetSourceFileMimeType) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of %s, got %s',
                    DigitalAssetSourceFileMimeType::class,
                    get_class($constraint)
                )
            );
        }
    }
}
