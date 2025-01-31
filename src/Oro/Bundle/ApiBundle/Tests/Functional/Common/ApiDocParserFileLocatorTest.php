<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Common;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\FileLocatorInterface;

class ApiDocParserFileLocatorTest extends WebTestCase
{
    private FileLocatorInterface $fileLocator;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->fileLocator = self::getContainer()->get('oro_api.resource_doc_parser.file_locator');
    }

    public function testLocateByBundleReference(): void
    {
        $path = '@OroApiBundle/Resources/doc/api/enum_option.md';
        $resolvedPath = $this->fileLocator->locate($path);
        self::assertNotEmpty($resolvedPath);
        self::assertNotEquals($path, $resolvedPath);
    }

    public function testLocateByRelativePath(): void
    {
        $expectedPath = $this->fileLocator->locate('@OroApiBundle/Resources/doc/api/enum_option.md');
        $currentPath = rtrim($this->fileLocator->locate('@OroApiBundle'), '/');
        $resolvedPath = $this->fileLocator->locate('Resources/doc/api/enum_option.md', $currentPath);
        self::assertEquals($expectedPath, $resolvedPath);
    }

    public function testLocateByRelativePathStartsWithSlash(): void
    {
        $expectedPath = $this->fileLocator->locate('@OroApiBundle/Resources/doc/api/enum_option.md');
        $currentPath = rtrim($this->fileLocator->locate('@OroApiBundle'), '/');
        $resolvedPath = $this->fileLocator->locate('/Resources/doc/api/enum_option.md', $currentPath);
        self::assertEquals($expectedPath, $resolvedPath);
    }
}
