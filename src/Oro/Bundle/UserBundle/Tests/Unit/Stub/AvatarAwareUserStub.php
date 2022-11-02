<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;

class AvatarAwareUserStub extends UserStub
{
    private ?File $avatar = null;

    public function setAvatar(File $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatar(): ?File
    {
        return $this->avatar;
    }
}
