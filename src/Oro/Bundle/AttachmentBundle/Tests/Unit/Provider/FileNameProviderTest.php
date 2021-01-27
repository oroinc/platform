<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProvider;

class FileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFileName()
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');

        $provider = new FileNameProvider();
        self::assertSame($file->getFilename(), $provider->getFileName($file));
    }
}
