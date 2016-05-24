<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Stub;

use Oro\Bundle\NoteBundle\Entity\Note;

class NoteStub extends Note
{
    public function getAttachment()
    {
        return null;
    }
}
