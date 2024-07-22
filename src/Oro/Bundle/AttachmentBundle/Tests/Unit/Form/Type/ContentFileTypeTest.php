<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\DataTransformer\ContentFileDataTransformerInterface;
use Oro\Bundle\AttachmentBundle\Form\Type\ContentFileType;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validation;

final class ContentFileTypeTest extends FormIntegrationTestCase
{
    private ContentFileType $formType;

    private ContentFileDataTransformerInterface&MockObject $contentFileDataTransformer;

    protected function setUp(): void
    {
        $this->contentFileDataTransformer = $this->createMock(
            ContentFileDataTransformerInterface::class
        );
        $this->formType = new ContentFileType($this->contentFileDataTransformer);

        parent::setUp();
    }

    public function testGetParent(): void
    {
        self::assertEquals(FileType::class, $this->formType->getParent());
    }

    public function testSubmitEmptyArray(): void
    {
        $file = new File();
        $this->contentFileDataTransformer->expects(self::once())
            ->method('transform')
            ->with(null)
            ->willReturn($file);

        $this->contentFileDataTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($file)
            ->willReturn(null);

        $form = $this->factory->create($this->formType::class);
        $form->submit([]);

        self::assertNull($form->getData());
    }

    public function testSubmitDeleteFile(): void
    {
        $file = new File();
        $this->contentFileDataTransformer->expects(self::once())
            ->method('transform')
            ->with(null)
            ->willReturn($file);

        $this->contentFileDataTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($file)
            ->willReturn(null);

        $form = $this->factory->create($this->formType::class);
        $form->submit(['emptyFile' => '1']);

        self::assertNull($form->getData());
    }

    public function testSubmitFile(): void
    {
        $file = new File();

        $this->contentFileDataTransformer->expects(self::once())
            ->method('transform')
            ->with('content')
            ->willReturn($file);

        $httpFile = new HttpFile('test.php', false);
        $this->contentFileDataTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($file)
            ->willReturn('content');

        $form = $this->factory->create($this->formType::class, 'content');
        $form->submit(['file' => $httpFile, 'emptyFile' => '']);

        self::assertEquals('', $form->getConfig()->getOption('fileName'));
        self::assertEquals('content', $form->getData());
    }

    public function testSubmitFileWithFileNameOption(): void
    {
        $file = new File();

        $this->contentFileDataTransformer->expects(self::once())
            ->method('transform')
            ->with('content')
            ->willReturn($file);

        $httpFile = new HttpFile('test.php', false);
        $this->contentFileDataTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($file)
            ->willReturn('content');

        $form = $this->factory->create($this->formType::class, 'content', ['fileName' => 'config.json']);
        $form->submit(['file' => $httpFile, 'emptyFile' => '']);

        self::assertEquals('config.json', $form->getConfig()->getOption('fileName'));
        self::assertEquals('content', $form->getData());
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->formType,
                new FileType($this->createMock(ExternalFileFactory::class))
            ], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}
