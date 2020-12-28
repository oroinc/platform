<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager\FileRemoval;

use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\DirectoryExtractor;
use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\ImageFileRemovalManagerConfig;

class ImageFileRemovalManagerConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfiguration()
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
