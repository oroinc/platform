<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\AttachmentBundle\Api\Processor\AddFileContentFormListener;
use Oro\Bundle\AttachmentBundle\Entity\File;

class AddFileContentFormListenerTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $fileManager;

    /** @var AddFileContentFormListener */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->fileManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\FileManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new AddFileContentFormListener($this->fileManager);
    }

    public function testProcessWithoutFormBuilder()
    {
        $this->processor->process($this->context);
        $this->assertNull($this->context->getFormBuilder());
    }

    public function testProcessWithEmptyData()
    {
        $formBuilder = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', 'text', ['property_path' => 'file'])
            ->add('originalFilename', 'text');

        $this->fileManager->expects($this->never())
            ->method('writeToTemporaryFile');

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);

        $model = new File();

        $form = $formBuilder->getForm();
        $form->setData($model);
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertNull($model->getFile());
    }

    public function testProcessWithoutOriginalFilename()
    {
        $formBuilder = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', 'text', ['property_path' => 'file'])
            ->add('originalFilename', 'text');

        $this->fileManager->expects($this->never())
            ->method('writeToTemporaryFile');

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);

        $model = new File();

        $form = $formBuilder->getForm();
        $form->setData($model);
        $form->submit(['content' => base64_encode('test')]);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->getErrors());

        $this->assertNull($model->getFile());
    }

    public function testProcessWithoutContent()
    {
        $originalFileName = 'test.txt';

        $formBuilder = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', 'text', ['property_path' => 'file'])
            ->add('originalFilename', 'text');

        $this->fileManager->expects($this->never())
            ->method('writeToTemporaryFile');

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);

        $model = new File();

        $form = $formBuilder->getForm();
        $form->setData($model);
        $form->submit(['originalFilename' => $originalFileName]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals($originalFileName, $model->getOriginalFilename());
        $this->assertNull($model->getFile());
    }

    public function testProcessWhenContentIsNull()
    {
        $originalFileName = 'test.txt';

        $formBuilder = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', 'text', ['property_path' => 'file'])
            ->add('originalFilename', 'text');

        $this->fileManager->expects($this->never())
            ->method('writeToTemporaryFile');

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);

        $model = new File();

        $form = $formBuilder->getForm();
        $form->setData($model);
        $form->submit(['originalFilename' => $originalFileName, 'content' => null]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals($originalFileName, $model->getOriginalFilename());
        $this->assertNull($model->getFile());
    }

    public function testProcessWithInvalidContent()
    {
        $formBuilder = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', 'text', ['property_path' => 'file'])
            ->add('originalFilename', 'text');

        $this->fileManager->expects($this->never())
            ->method('writeToTemporaryFile');

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);

        $model = new File();

        $form = $formBuilder->getForm();
        $form->setData($model);
        $form->submit(['originalFilename' => 'test.txt', 'content' => '!!!']);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->getErrors());
    }

    public function testProcessWithValidContentButWithoutMimeType()
    {
        $content = 'test';
        $originalFileName = 'test.txt';

        $file = new ComponentFile(__DIR__ . '/../../Fixtures/testFile/test.txt');

        $formBuilder = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', 'text', ['property_path' => 'file'])
            ->add('originalFilename', 'text')
            ->add('mimeType', 'text');

        $this->fileManager->expects($this->once())
            ->method('writeToTemporaryFile')
            ->with($content, $originalFileName)
            ->willReturn($file);

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);

        $model = new File();

        $form = $formBuilder->getForm();
        $form->setData($model);
        $form->submit(['originalFilename' => $originalFileName, 'content' => base64_encode($content)]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals($originalFileName, $model->getOriginalFilename());
        $this->assertSame($file, $model->getFile());
    }

    public function testProcessWithValidContentAndWithMimeType()
    {
        $content = 'test';
        $originalFileName = 'test.txt';
        $mimeType = 'text/plain';

        $file = new ComponentFile(__DIR__ . '/../../Fixtures/testFile/test.txt');

        $formBuilder = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => File::class])
            ->add('content', 'text', ['property_path' => 'file'])
            ->add('originalFilename', 'text')
            ->add('mimeType', 'text');

        $this->fileManager->expects($this->once())
            ->method('writeToTemporaryFile')
            ->with($content, $originalFileName)
            ->willReturn($file);

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);

        $model = new File();

        $form = $formBuilder->getForm();
        $form->setData($model);
        $form->submit(
            [
                'originalFilename' => $originalFileName,
                'content'          => base64_encode($content),
                'mimeType'         => $mimeType
            ]
        );
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals($originalFileName, $model->getOriginalFilename());
        $expectedFile = new UploadedFile(
            $file->getPathname(),
            $originalFileName,
            $mimeType
        );
        $this->assertEquals($expectedFile, $model->getFile());
    }
}
