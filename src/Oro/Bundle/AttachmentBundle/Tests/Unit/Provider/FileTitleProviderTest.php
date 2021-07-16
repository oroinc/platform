<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProvider;

class FileTitleProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTitle(): void
    {
        $file = new File();
        $file->setOriginalFilename($title = 'sample title');

        $this->assertEquals($title, (new FileTitleProvider())->getTitle($file));
    }
}
