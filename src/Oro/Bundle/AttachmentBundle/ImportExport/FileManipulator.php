<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sets {@see File}::$file property by either uploading or cloning it from another {@see File}.
 */
class FileManipulator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private FileManager $fileManager;

    private AuthorizationCheckerInterface $authorizationChecker;

    private FileImportStrategyHelper $fileImportStrategyHelper;

    private TranslatorInterface $translator;

    public function __construct(
        FileManager $fileManager,
        AuthorizationCheckerInterface $authorizationChecker,
        FileImportStrategyHelper $fileImportStrategyHelper,
        TranslatorInterface $translator
    ) {
        $this->fileManager = $fileManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->fileImportStrategyHelper = $fileImportStrategyHelper;
        $this->translator = $translator;
        $this->logger = new NullLogger();
    }

    /**
     * Sets File::$file property by cloning it from another file.
     *
     * @param File $file
     * @param File $originFile
     * @param object $entity
     * @param string $fieldName
     *
     * @return string[] Errors occurred during cloning from origin file.
     */
    public function setFileFromOriginFile(
        File $file,
        File $originFile,
        object $entity,
        string $fieldName
    ): array {
        $errors = [];
        $parameters = [
            '%origin_id%' => $originFile->getId(),
            '%origin_uuid%' => $originFile->getUuid(),
            '%fieldname%' => $this->getFieldLabel($entity, $fieldName),
        ];

        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $originFile)) {
            $parameters['%error%'] = $this->translator
                ->trans('oro.attachment.import.failed_to_clone_forbidden', $parameters);
        } else {
            try {
                $innerFile = $this->fileManager->getFileFromFileEntity($originFile);

                // SplFileInfo which is set here will be processed later by oro_attachment.listener.file_listener.
                $file->setFile($innerFile);
                $file->setOriginalFilename($originFile->getOriginalFilename());
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to clone a file during import', ['e' => $exception]);
                $parameters['%error%'] = $exception->getMessage();
            }
        }

        if (!$file->getFile()) {
            if (!isset($parameters['%error%'])) {
                $parameters['%error%'] = $this->translator
                    ->trans('oro.attachment.import.failed_to_clone_origin_file_empty');
            }

            $errors[] = $this->translator->trans('oro.attachment.import.failed_to_clone', $parameters);
        }

        return $errors;
    }

    /**
     * Sets File::$file property by uploading a file from $fileForUpload.
     *
     * @param File $file
     * @param \SplFileInfo $fileForUpload
     * @param object $entity
     * @param string $fieldName
     *
     * @return string[] Errors occurred during upload.
     */
    public function setFileFromUpload(File $file, \SplFileInfo $fileForUpload, object $entity, string $fieldName): array
    {
        $errors = [];
        $parameters = [
            '%fieldname%' => $this->getFieldLabel($entity, $fieldName),
        ];
        try {
            if ($fileForUpload instanceof SymfonyFile) {
                $this->fileManager->setFileFromPath($file, $fileForUpload->getPathname());
            } elseif ($fileForUpload instanceof ExternalFile) {
                $errors = $this->fileImportStrategyHelper->validateExternalFileUrl($fileForUpload, $entity, $fieldName);
                if (!$errors) {
                    $this->fileManager->setExternalFileFromUrl($file, $fileForUpload->getUrl());
                }
            } else {
                throw new \LogicException(
                    sprintf(
                        'The object of type %s returned from %s is not supported. Expected one of %s',
                        get_debug_type($fileForUpload),
                        $parameters['%fieldname%'],
                        implode(', ', [SymfonyFile::class, ExternalFile::class])
                    )
                );
            }
        } catch (ExternalFileNotAccessibleException $exception) {
            $errors[] = $this->translator->trans(
                'oro.attachment.import.failed_to_process_external_file',
                $parameters + ['%url%' => $exception->getUrl(), '%error%' => $exception->getReason()]
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to upload a file during import', ['e' => $exception]);

            $errors = [
                $this->translator->trans(
                    'oro.attachment.import.failed_to_upload',
                    $parameters + ['%path%' => $fileForUpload->getPathname(), '%error%' => $exception->getMessage()]
                ),
            ];
        }

        return $errors;
    }

    private function getFieldLabel(object $entity, string $fieldName): string
    {
        $entityClass = $this->fileImportStrategyHelper->getClass($entity);

        return $this->fileImportStrategyHelper->getFieldLabel($entityClass, $fieldName);
    }
}
