<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Twig;

use Oro\Bundle\ImportExportBundle\Twig\BasenameTwigExtension;
use Twig\TwigFilter;

class BasenameTwigExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFilters()
    {
        $extension = new BasenameTwigExtension();

        self::assertEquals(
            [
                new TwigFilter('basename', [$extension, 'basenameFilter'])
            ],
            $extension->getFilters()
        );
    }

    public function testBasenameFilter()
    {
        self::assertSame('3', (new BasenameTwigExtension())->basenameFilter('1\\2\\3'));
    }
}
