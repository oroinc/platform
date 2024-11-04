<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData\CustomizeFormDataProcessorTestCase;
use Oro\Bundle\AttachmentBundle\Api\Processor\HandleFileContent;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class HandleFileContentTest extends CustomizeFormDataProcessorTestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var HandleFileContent */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileManager = $this->createMock(FileManager::class);

        $this->processor = new HandleFileContent($this->fileManager);
    }

    public function testProcessWithEmptyData(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', TextType::class, ['property_path' => 'file'])
            ->add('originalFilename', TextType::class)
            ->getForm();

        $data = [];

        $this->fileManager->expects(self::never())
            ->method('writeToTemporaryFile');

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors());

        self::assertSame([], $this->context->getData());
    }

    public function testProcessWithoutOriginalFilename(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', TextType::class, ['property_path' => 'file'])
            ->add('originalFilename', TextType::class)
            ->getForm();

        $data = ['content' => base64_encode('test')];

        $this->fileManager->expects(self::never())
            ->method('writeToTemporaryFile');

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertCount(1, $form->getErrors());

        self::assertSame(['content' => null], $this->context->getData());
    }

    public function testProcessWithoutContent(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', TextType::class, ['property_path' => 'file'])
            ->add('originalFilename', TextType::class)
            ->getForm();

        $data = ['originalFilename' => 'test.txt'];

        $this->fileManager->expects(self::never())
            ->method('writeToTemporaryFile');

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors());

        self::assertSame($data, $this->context->getData());
    }

    public function testProcessWhenContentIsNull(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', TextType::class, ['property_path' => 'file'])
            ->add('originalFilename', TextType::class)
            ->getForm();

        $data = ['originalFilename' => 'test.txt', 'content' => null];

        $this->fileManager->expects(self::never())
            ->method('writeToTemporaryFile');

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors());

        self::assertSame($data, $this->context->getData());
    }

    public function testProcessWithInvalidContent(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', TextType::class, ['property_path' => 'file'])
            ->add('originalFilename', TextType::class)
            ->getForm();

        $data = ['originalFilename' => 'test.txt', 'content' => '!!!'];

        $this->fileManager->expects(self::never())
            ->method('writeToTemporaryFile');

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertCount(1, $form->getErrors());

        self::assertSame(['originalFilename' => 'test.txt', 'content' => null], $this->context->getData());
    }

    public function testProcessWithValidContentButWithoutMimeType(): void
    {
        $content = 'test';
        $originalFileName = 'test.txt';

        $file = new ComponentFile(__DIR__ . '/../../Fixtures/testFile/test.txt');

        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', TextType::class, ['property_path' => 'file'])
            ->add('originalFilename', TextType::class)
            ->add('mimeType', TextType::class)
            ->getForm();

        $data = ['originalFilename' => $originalFileName, 'content' => base64_encode($content)];

        $this->fileManager->expects(self::once())
            ->method('writeToTemporaryFile')
            ->with($content, $originalFileName)
            ->willReturn($file);

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors());

        $handledData = $this->context->getData();
        self::assertEquals($originalFileName, $handledData['originalFilename']);
        self::assertSame($file, $handledData['content']);
    }

    public function testProcessWithValidContentAndWithMimeType(): void
    {
        $content = 'test';
        $originalFileName = 'test.txt';
        $mimeType = 'text/plain';

        $file = new ComponentFile(__DIR__ . '/../../Fixtures/testFile/test.txt');

        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', TextType::class, ['property_path' => 'file'])
            ->add('originalFilename', TextType::class)
            ->add('mimeType', TextType::class)
            ->getForm();

        $data = [
            'originalFilename' => $originalFileName,
            'content'          => base64_encode($content),
            'mimeType'         => $mimeType
        ];

        $this->fileManager->expects(self::once())
            ->method('writeToTemporaryFile')
            ->with($content, $originalFileName)
            ->willReturn($file);

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertCount(0, $form->getErrors());

        $handledData = $this->context->getData();
        self::assertEquals($originalFileName, $handledData['originalFilename']);
        self::assertEquals(
            new UploadedFile($file->getPathname(), $originalFileName, $mimeType),
            $handledData['content']
        );
    }
}
