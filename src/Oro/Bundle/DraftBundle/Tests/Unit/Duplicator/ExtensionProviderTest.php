<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator;

use Oro\Bundle\DraftBundle\Duplicator\Extension\AbstractDuplicatorExtension;
use Oro\Bundle\DraftBundle\Duplicator\ExtensionProvider;

class ExtensionProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetExtensions(): void
    {
        $extension = $this->createMock(AbstractDuplicatorExtension::class);
        $extensions = [$extension];
        $extensionProvider = new ExtensionProvider($extensions);
        $this->assertEquals($extensions, $extensionProvider->getExtensions());
    }
}
