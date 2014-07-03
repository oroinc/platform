<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;

class AttachmentTest extends EntityTestAbstract
{
    protected function setUp()
    {
        $this->entity = new Attachment();
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $comment = 'test comment';
        $file = new File();

        return [
            'comment' => ['comment', $comment, $comment],
            'file' => ['file', $file, $file],
        ];
    }
}
