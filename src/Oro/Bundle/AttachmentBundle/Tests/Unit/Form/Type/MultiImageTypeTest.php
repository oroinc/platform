<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiFileType;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiImageTypeTest extends \PHPUnit\Framework\TestCase
{
    private MultiFileType|MultiImageType $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->type = new MultiImageType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('entry_options', []);
        $this->type->configureOptions($resolver);

        self::assertEquals([
            'entry_options' => [
                'file_type' => ImageType::class,
            ],
        ], $resolver->resolve([]));
    }

    public function testConfigureOptionsAddsFileTypeWhenEntryOptionsNotEmpty(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('entry_options', []);
        $this->type->configureOptions($resolver);

        self::assertEquals([
            'entry_options' => [
                'sample_key' => 'sample_value',
                'file_type' => ImageType::class,
            ],
        ], $resolver->resolve(['entry_options' => ['sample_key' => 'sample_value']]));
    }

    public function testConfigureOptionsDoesNothingWhenEntryOptionsContainsFileType(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('entry_options', []);
        $this->type->configureOptions($resolver);

        self::assertEquals([
            'entry_options' => [
                'file_type' => FileType::class,
            ],
        ], $resolver->resolve(['entry_options' => ['file_type' => FileType::class]]));
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals(MultiImageType::TYPE, $this->type->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(MultiFileType::class, $this->type->getParent());
    }
}
