<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Fixtures;

use Nelmio\Alice\FileLocator\DefaultFileLocator;
use Nelmio\Alice\Parser\Chainable\YamlParser;
use Nelmio\Alice\Parser\IncludeProcessor\DefaultIncludeProcessor;
use Nelmio\Alice\Parser\RuntimeCacheParser;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\IncludeProcessor;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Parser;

class IncludeProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $kernel;

    /** @var RuntimeCacheParser */
    private $parser;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);

        $fileLocator = new DefaultFileLocator();

        $this->parser = new RuntimeCacheParser(
            new YamlParser(new Parser()),
            $fileLocator,
            new IncludeProcessor(new DefaultIncludeProcessor($fileLocator), $this->kernel)
        );
    }

    /**
     * @dataProvider includeFilesDataProvider
     */
    public function testIncludeFiles(string $path, ?string $expectsKernelCallWith)
    {
        if ($expectsKernelCallWith) {
            $this->kernel->expects($this->once())
                ->method('locateResource')
                ->with($expectsKernelCallWith)
                ->willReturn(__DIR__ . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_include.yml');
        } else {
            $this->kernel->expects($this->never())->method('locateResource');
        }

        $data = $this->parser->parse($path);

        $this->assertSame(['test' => ['test' => null]], $data);
    }

    public function includeFilesDataProvider(): array
    {
        return [
            [
                'path' => __DIR__ . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_1.yml',
                'expectsKernelCallWith' => '@OroStubBundle/Tests/Behat/Features/Fixtures/test_include.yml'
            ],
            [
                'path' => __DIR__ . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_2.yml',
                'expectsKernelCallWith' => '@OroStubBundle/Tests/Behat/Features/Fixtures/test_include.yml'
            ],
            [
                'path' => __DIR__ . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_3.yml',
                'expectsKernelCallWith' => null
            ]
        ];
    }
}
