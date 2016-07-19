<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Gaufrette\Exception\FileNotFound;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataContext;
use Oro\Bundle\AttachmentBundle\Api\Processor\ComputeFileContent;

class ComputeFileContentTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var CustomizeLoadedDataContext */
    protected $context;

    /** @var ComputeFileContent */
    protected $processor;

    protected function setUp()
    {
        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');

        $this->context = new CustomizeLoadedDataContext();
        $this->processor = new ComputeFileContent($this->attachmentManager, $this->logger);
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

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt'],
            $this->context->getResult()
        );
    }

    public function testProcessWhenContentFieldIsAlreadyExist()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content');

        $this->context->setResult(['filename' => 'test.txt', 'content' => 'test']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt', 'content' => 'test'],
            $this->context->getResult()
        );
    }

    public function testProcessWhenContentFieldDoesNotExistButNoConfigForFileNameField()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content');

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt', 'content' => null],
            $this->context->getResult()
        );
    }

    public function testProcessWhenContentFieldDoesNotExistButFileNameFieldDoesNotExistAsWell()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content');
        $config->addField('filename');

        $this->context->setResult([]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['content' => null],
            $this->context->getResult()
        );
    }

    public function testProcessWhenContentFieldDoesNotExist()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content');
        $config->addField('filename');

        $this->attachmentManager->expects($this->once())
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

    public function testProcessWhenContentFieldDoesNotExistAndFileNameIsEmpty()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content');
        $config->addField('filename');

        $this->attachmentManager->expects($this->never())
            ->method('getContent');

        $this->context->setResult(['filename' => null]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => null, 'content' => null],
            $this->context->getResult()
        );
    }

    public function testProcessWhenContentFieldDoesNotExistAndFileIsEmpty()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content');
        $config->addField('filename');

        $this->attachmentManager->expects($this->once())
            ->method('getContent')
            ->with('test.txt')
            ->willReturn('');

        $this->context->setResult(['filename' => 'test.txt']);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            ['filename' => 'test.txt', 'content' => base64_encode('')],
            $this->context->getResult()
        );
    }

    public function testProcessWhenContentFieldDoesNotExistAndFileIsNotFound()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content');
        $config->addField('filename');

        $exception = new FileNotFound('test.txt');
        $this->attachmentManager->expects($this->once())
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
            ['filename' => 'test.txt', 'content' => null],
            $this->context->getResult()
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage some error
     */
    public function testProcessWhenContentFieldDoesNotExistAndUnexpectedExceptionOccurred()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('content');
        $config->addField('filename');

        $exception = new \Exception('some error');
        $this->attachmentManager->expects($this->once())
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
