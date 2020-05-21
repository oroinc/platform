<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiFileType;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiImageTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var MultiFileType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->type = new MultiImageType();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with([
                'entry_options' => [
                    'file_type' => ImageType::class,
                ],
            ]);

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(MultiImageType::TYPE, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(MultiImageType::TYPE, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(MultiFileType::class, $this->type->getParent());
    }
}
