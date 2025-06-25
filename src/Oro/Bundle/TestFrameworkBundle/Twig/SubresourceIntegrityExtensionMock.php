<?php

namespace Oro\Bundle\TestFrameworkBundle\Twig;

use Oro\Bundle\AssetBundle\Twig\SubresourceIntegrityExtension;

/**
 * Mock Subresource integrity twig extension for test env because integrity assets is not used.
 */
class SubresourceIntegrityExtensionMock extends SubresourceIntegrityExtension
{
    public function getIntegrityHash(string $asset): string
    {
        return 'integrity_hash_mock';
    }
}
