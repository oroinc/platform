<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ApiDocFileLocator;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class ApiDocFileLocatorTest extends TestCase
{
    private KernelInterface $kernel;
    private ApiDocFileLocator $fileLocator;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->fileLocator = new ApiDocFileLocator($this->kernel);
    }

    public function testSetPaths(): void
    {
        $paths = ['/path/to/docs', '/another/path'];

        $this->fileLocator->setPaths($paths);

        $actualPaths = ReflectionUtil::getPropertyValue($this->fileLocator, 'paths');
        self::assertEquals($paths, $actualPaths);
    }

    public function testSetPathsWithEmptyArray(): void
    {
        $this->fileLocator->setPaths([]);

        $actualPaths = ReflectionUtil::getPropertyValue($this->fileLocator, 'paths');
        self::assertEquals([], $actualPaths);
    }
}
