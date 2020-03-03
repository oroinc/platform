<?php

namespace Oro\Bundle\AttachmentBundle\Model;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Provides not managed instance of file with ability to set id.
 */
class FileModel extends File
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
