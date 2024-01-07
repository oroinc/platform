<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;

class LoadEmailToOtherFolderData extends LoadEmailData
{
    /**
     * {@inheritDoc}
     */
    protected function getFolder(EmailOrigin $origin): EmailFolder
    {
        $folder = new EmailFolder();
        $folder->setName('Other');
        $folder->setFullName('Other');
        $folder->setType(FolderType::OTHER);

        $origin->addFolder($folder);

        return $folder;
    }
}
