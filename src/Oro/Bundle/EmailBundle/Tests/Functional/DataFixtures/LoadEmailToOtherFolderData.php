<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Model\FolderType;

class LoadEmailToOtherFolderData extends LoadEmailData
{
    /**
     * {@inheritdoc}
     */
    protected function getFolder($origin)
    {
        $folder = new EmailFolder();
        $folder->setName('Other');
        $folder->setFullName('Other');
        $folder->setType(FolderType::OTHER);

        $origin->addFolder($folder);

        return $folder;
    }
}
