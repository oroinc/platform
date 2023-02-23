<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\CustomizeLoadedDataProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\AttachmentBundle\Api\Processor\ComputeFileContent;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Psr\Log\LoggerInterface;

class ComputeFileContentTest extends CustomizeLoadedDataProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ComputeFileContent */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileManager = $this->createMock(FileManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ComputeFileContent($this->fileManager, $this->logger);
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

    public function testProcessWhenUnexpectedExceptionOccurred()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error');

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

    public function testProcessWhenFileHasExternalUrl()
    {
        $externalUrl = 'http://example.org/test.txt';
        $config = new EntityDefinitionConfig();
        $config->addField('content')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $config->addField('filename')->setExcluded();
        $config->addField('externalUrl')->setExcluded();

        $this->context->setResult(['filename' => 'test.txt', 'externalUrl' => $externalUrl]);
        $this->context->setConfig($config);

        $this->fileManager->expects(self::never())
            ->method('getContent')
            ->with($externalUrl, true);

        $this->processor->process($this->context);
    }
}
