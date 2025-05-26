<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager\FileRemoval;

use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\DirectoryExtractor;
use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\ImageFileRemovalManagerConfig;
use PHPUnit\Framework\TestCase;

class ImageFileRemovalManagerConfigTest extends TestCase
{
    public function testGetConfiguration(): void
    {
        $configuration = new ImageFileRemovalManagerConfig();
        self::assertEquals(
            [
                'filter' => new DirectoryExtractor('/^(attachment\/filter\/\w+\/\w+\/\d+)\/\w+/', false),
                'resize' => new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', true)
            ],
            $configuration->getConfiguration()
        );
    }
}
