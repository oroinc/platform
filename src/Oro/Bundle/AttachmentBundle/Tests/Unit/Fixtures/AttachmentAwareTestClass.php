<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AttachmentBundle\Entity\File;

class AttachmentAwareTestClass extends TestClass
{
    public ?File $attachment = null;

    public function setAttachment(File $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getAttachment(): ?File
    {
        return $this->attachment;
    }
}
