<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Twig;

use Oro\Bundle\ImportExportBundle\Twig\BasenameTwigExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class BasenameTwigExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $extension = new BasenameTwigExtension();

        self::assertEquals(
            [
                new TwigFilter('basename', [$extension, 'basenameFilter'])
            ],
            $extension->getFilters()
        );
    }

    public function testBasenameFilter(): void
    {
        self::assertSame('3', (new BasenameTwigExtension())->basenameFilter('1\\2\\3'));
    }
}
