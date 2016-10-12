<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMdParser;

class ApiDocMdParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var ApiDocMdParser */
    protected $apiDocMDParser;

    /** @var FileLocator|\PHPUnit_Framework_MockObject_MockObject */
    protected $fileLocator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fileLocator = $this->getMockBuilder('Symfony\Component\HttpKernel\Config\FileLocator')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function test()
    {
        $inputPath = __DIR__ . '/Fixtures/apidoc.md';
        $expectation = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/apidoc.yml'));

        $this->fileLocator->expects($this->once())
            ->method('locate')
            ->with('@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures/apidoc.md')
            ->willReturn($inputPath);
        $this->apiDocMDParser = new ApiDocMdParser($this->fileLocator);
        $this->apiDocMDParser->parseDocumentationResource('@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures/apidoc.md');

        $this->assertSame($expectation, $this->apiDocMDParser->loadedDocumentation);
    }
}
