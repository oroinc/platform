<?php

namespace Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\AttachmentBundle\Entity\Attachment as BaseAttachment;

class Attachment extends BaseAttachment
{
    protected $supportTarget = true;

    public function setSupportTarget($supportTarget)
    {
        $this->supportTarget = $supportTarget;
    }

    public function supportTarget()
    {
        return $this->supportTarget;
    }
}
