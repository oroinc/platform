<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\AttachmentBundle\Entity\Attachment as BaseAttachment;

class Attachment extends BaseAttachment
{
    protected $supportTarget = true;

    /**
     * @param $supportTarget
     */
    public function setSupportTarget($supportTarget)
    {
        $this->supportTarget = $supportTarget;
    }

    /**
     * @param string $targetClass
     *
     * @return bool
     */
    public function supportTarget($targetClass)
    {
        return $this->supportTarget;
    }
}
