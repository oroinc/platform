<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\AttachmentBundle\Api\Processor\ComputeFileContent;

class ComputeFileContentTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var CustomizeLoadedDataContext */
    protected $context;

    /** @var ComputeFileContent */
    protected $processor;

    protected function setUp()
    {
        $this->fileManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\FileManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock('Psr\Log\LoggerInterface');

        $this->context = new CustomizeLoadedDataContext();
        $this->processor = new ComputeFileContent($this->fileManager, $this->logger);
    }

    public function testProcessWhenNoData()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWhenNoConfigForContentField()
    {
        $config = new EntityDefinitionConfig();

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt'],
            $this->context->getResult()
        );
    }

    public function testProcessWhenContentFieldIsExcluded()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content')->setExcluded();
        $config->addField('filename')->setExcluded();

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt'],
            $this->context->getResult()
        );
    }

    public function testProcessWhenFileNameFieldDoesNotExist()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $config->addField('filename')->setExcluded();

        $this->context->setResult([]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [],
            $this->context->getResult()
        );
    }

    public function testProcessWhenContentFieldShouldBeSet()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $config->addField('filename')->setExcluded();

        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->with('test.txt')
            ->willReturn('test');

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt', 'content' => base64_encode('test')],
            $this->context->getResult()
        );
    }

    public function testProcessWhenFileNameIsEmpty()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $config->addField('filename')->setExcluded();

        $this->fileManager->expects($this->never())
            ->method('getContent');

        $this->context->setResult(['filename' => null]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => null],
            $this->context->getResult()
        );
    }

    public function testProcessWhenFileIsEmpty()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $config->addField('filename')->setExcluded();

        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->with('test.txt')
            ->willReturn(null);

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt'],
            $this->context->getResult()
        );
    }

    public function testProcessWhenFileIsNotFound()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $config->addField('filename')->setExcluded();

        $exception = new FileNotFound('test.txt');
        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->with('test.txt')
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with('The content for "test.txt" file cannot be loaded.', ['exception' => $exception]);

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt'],
            $this->context->getResult()
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage some error
     */
    public function testProcessWhenUnexpectedExceptionOccurred()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $config->addField('filename')->setExcluded();

        $exception = new \Exception('some error');
        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->with('test.txt')
            ->willThrowException($exception);
        $this->logger->expects($this->never())
            ->method('error');

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }
}
