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
    /** @var FileItemType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->type = new FileItemType();
    }

    public function testBuildForm()
    {
        /* @var $builder FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::at(0))
            ->method('add')
            ->with('sortOrder', NumberType::class, ['block_prefix' => 'oro_attachment_file_item_sortOrder'])
            ->willReturnSelf();

        $builder->expects(self::at(1))
            ->method('add')
            ->with('file', FileType::class, [
                'allowDelete' => false,
                'block_prefix' => 'oro_attachment_file_item_file'
            ])
            ->willReturnSelf();

        $this->type->buildForm($builder, ['file_type' => FileType::class]);
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with([
                'data_class' => FileItem::class,
                'file_type' => FileType::class,
            ]);

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(FileItemType::TYPE, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(FileItemType::TYPE, $this->type->getBlockPrefix());
    }
}
