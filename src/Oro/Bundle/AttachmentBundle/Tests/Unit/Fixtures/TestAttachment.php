<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AttachmentBundle\Entity\File;

class TestAttachment extends File
{
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
