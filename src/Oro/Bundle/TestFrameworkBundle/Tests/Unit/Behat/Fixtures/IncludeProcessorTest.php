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
    /** @var RuntimeCacheParser */
    protected $parser;

    /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $kernel;

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->parser);
    }

    /**
     * @dataProvider includeFilesDataProvider
     *
     * @param string $path
     * @param null|string $expectsKernelCallWith
     */
    public function testIncludeFiles($path, $expectsKernelCallWith = null)
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

        $this->assertSame(['test' => ['test' => null,],], $data);
    }

    /**
     * @return \Generator
     */
    public function includeFilesDataProvider()
    {
        yield [
            'path' => __DIR__ . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_1.yml',
            'expectsKernelCallWith' => '@OroStubBundle/Tests/Behat/Features/Fixtures/test_include.yml',
        ];
        yield [
            'path' => __DIR__ . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_2.yml',
            'expectsKernelCallWith' => '@OroStubBundle/Tests/Behat/Features/Fixtures/test_include.yml',
        ];
        yield [
            'path' => __DIR__ . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_3.yml',
            'expectsKernelCallWith' => null,
        ];
    }
}
