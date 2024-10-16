<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Stub;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;

class AttachmentProviderStub extends AttachmentProvider
{
    /**
     * @param $entity
     *
     * @return array
     */
    #[\Override]
    public function getAttachmentInfo($entity)
    {
        return [];
    }
}
