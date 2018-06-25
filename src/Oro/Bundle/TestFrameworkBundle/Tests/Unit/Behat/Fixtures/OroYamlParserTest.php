<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Symfony\Component\HttpKernel\KernelInterface;

class OroYamlParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var OroYamlParser */
    protected $parser;

    /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $kernel;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->parser = new OroYamlParser();
        $this->parser->setKernel($this->kernel);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
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
                ->with($expectsKernelCallWith, null, true)
                ->willReturn(dirname(__FILE__) . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_include.yml');
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
            'path' => dirname(__FILE__) . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_1.yml',
            'expectsKernelCallWith' => '@OroStubBundle/Tests/Behat/Features/Fixtures/test_include.yml',
        ];
        yield [
            'path' => dirname(__FILE__) . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_2.yml',
            'expectsKernelCallWith' => '@OroStubBundle/Tests/Behat/Features/Fixtures/test_include.yml',
        ];
        yield [
            'path' => dirname(__FILE__) . '/OroStubBundle/Tests/Behat/Features/Fixtures/test_fixture_3.yml',
            'expectsKernelCallWith' => null,
        ];
    }
}
