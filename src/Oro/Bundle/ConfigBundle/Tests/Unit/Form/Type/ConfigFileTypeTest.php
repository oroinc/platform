<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigFileType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validation;

class ConfigFileTypeTest extends FormIntegrationTestCase
{
    const FILE1_ID = 1;
    const FILE2_ID = 2;

    /** @var ConfigFileType */
    protected $formType;

    /** @var ConfigFileDataTransformer|MockObject */
    protected $transformer;

    protected function setUp(): void
    {
        $this->transformer = $this->createMock(ConfigFileDataTransformer::class);
        $this->formType = new ConfigFileType($this->transformer);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->formType);
    }

    public function testGetParent()
    {
        static::assertEquals(FileType::class, $this->formType->getParent());
    }

    public function testSubmitNull()
    {
        $this->transformer->expects(static::once())
            ->method('setFileConstraints')
            ->with(static::isType(IsType::TYPE_ARRAY));

        $file = new File();
        $this->transformer->expects(static::once())
            ->method('transform')
            ->with(null)
            ->willReturn($file);

        $this->transformer->expects(static::once())
            ->method('reverseTransform')
            ->with($file)
            ->willReturn(null);

        $form = $this->factory->create(ConfigFileType::class, null);
        $form->submit(null);

        static::assertNull($form->getData());
    }

    public function testSubmitFile()
    {
        $file = new File();

        $this->transformer->expects(static::once())
            ->method('setFileConstraints')
            ->with(static::isType(IsType::TYPE_ARRAY));

        $this->transformer->expects(static::once())
            ->method('transform')
            ->with(self::FILE1_ID)
            ->willReturn($file);

        $httpFile = new HttpFile('test.php', false);
        $this->transformer->expects(static::once())
            ->method('reverseTransform')
            ->with($file)
            ->willReturn(self::FILE1_ID);

        $form = $this->factory->create(ConfigFileType::class, self::FILE1_ID);
        $form->submit(['file' => $httpFile]);

        static::assertEquals(self::FILE1_ID, $form->getData());
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ConfigFileType::class => $this->formType
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}
