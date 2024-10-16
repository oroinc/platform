<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Api\Repository\EmailOriginRepository;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBatchProcessor;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles submitted email folders to avoid duplication of folders that already exist in the database.
 */
class HandleEmailFolders implements ProcessorInterface
{
    private EmailEntityBuilder $emailEntityBuilder;
    private EmailOriginRepository $emailOriginRepository;

    public function __construct(EmailEntityBuilder $emailEntityBuilder, EmailOriginRepository $emailOriginRepository)
    {
        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->emailOriginRepository = $emailOriginRepository;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        if (!$context->getForm()->get('folders')->isSubmitted()) {
            return;
        }

        /** @var EmailUser $emailUser */
        $emailUser = $context->getData();
        $emailOrigin = $this->emailOriginRepository->getEmailOrigin(
            $emailUser->getOrganization()->getId(),
            $emailUser->getOwner()->getId()
        );
        $batch = $this->emailEntityBuilder->getBatch();
        /** @var EmailFolder[] $folders */
        $folders = $emailUser->getFolders()->toArray();
        foreach ($folders as $folder) {
            $folderType = $folder->getType();
            $folderFullName = $folder->getFullName();
            $emailOriginFolder = $this->findFolderInOrigin($emailOrigin, $folderType, $folderFullName);
            if (null === $emailOriginFolder) {
                $emailOrigin->addFolder($folder);
                $batchFolder = $this->findFolderInBatch($batch, $folderType, $folderFullName);
                if (null === $batchFolder) {
                    $batch->addFolder($folder);
                } else {
                    $emailUser->removeFolder($folder);
                    $emailUser->addFolder($batchFolder);
                    $this->syncFolderName($folder, $batchFolder);
                }
            } else {
                $emailUser->removeFolder($folder);
                $emailUser->addFolder($emailOriginFolder);
                $batch->addFolder($emailOriginFolder);
                $this->syncFolderName($folder, $emailOriginFolder);
            }
        }
    }

    private function findFolderInOrigin(EmailOrigin $emailOrigin, string $type, string $fullName): ?EmailFolder
    {
        return $emailOrigin->getFolder($type, FolderType::OTHER === $type ? $fullName : null);
    }

    private function findFolderInBatch(EmailEntityBatchProcessor $batch, string $type, string $fullName): ?EmailFolder
    {
        $batchFolders = $batch->getFolders();
        foreach ($batchFolders as $batchFolder) {
            if ($batchFolder->getType() === $type
                && (FolderType::OTHER !== $type || $batchFolder->getFullName() === $fullName)
            ) {
                return $batchFolder;
            }
        }

        return null;
    }

    private function syncFolderName(EmailFolder $sourceFolder, EmailFolder $targetFolder): void
    {
        if ($targetFolder->getName() !== $sourceFolder->getName()) {
            $targetFolder->setName($sourceFolder->getName());
        }
        if ($targetFolder->getFullName() !== $sourceFolder->getFullName()) {
            $targetFolder->setFullName($sourceFolder->getFullName());
        }
    }
}
