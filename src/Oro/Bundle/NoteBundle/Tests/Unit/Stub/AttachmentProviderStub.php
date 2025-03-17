<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Stub;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;

class AttachmentProviderStub extends AttachmentProvider
{
    #[\Override]
    public function getAttachmentInfo(object $entity): array
    {
        return [];
    }
}
