<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AttachmentBundle\Entity\File;

class TestFile extends File
{
    /**
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }
}
