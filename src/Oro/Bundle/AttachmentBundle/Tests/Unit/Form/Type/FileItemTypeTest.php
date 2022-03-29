<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Form\Type\FileItemType;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileItemTypeTest extends \PHPUnit\Framework\TestCase
{
    private FileItemType $type;

    protected function setUp(): void
    {
        $this->type = new FileItemType();
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->withConsecutive(
                ['sortOrder', NumberType::class, ['block_prefix' => 'oro_attachment_file_item_sortOrder']],
                [
                    'file',
                    FileType::class,
                    [
                        'allowDelete' => false,
                        'block_prefix' => 'oro_attachment_file_item_file',
                        'checkEmptyFile' => false,
                    ],
                ]
            )
            ->willReturnSelf();

        $this->type->buildForm(
            $builder,
            ['file_type' => FileType::class, 'file_options' => ['checkEmptyFile' => false]]
        );
    }

    public function testConfigureOptions(): void
    {
        $optionsResolver = new OptionsResolver();

        $this->type->configureOptions($optionsResolver);

        self::assertEquals([
            'data_class' => FileItem::class,
            'file_type' => FileType::class,
            'file_options' => [],
        ], $optionsResolver->resolve([]));
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals(FileItemType::TYPE, $this->type->getBlockPrefix());
    }
}
