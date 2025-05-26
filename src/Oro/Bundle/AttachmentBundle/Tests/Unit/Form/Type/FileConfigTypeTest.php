<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileConfigType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileConfigTypeTest extends TestCase
{
    private FileConfigType $type;

    #[\Override]
    protected function setUp(): void
    {
        $configManager = $this->createMock(ConfigManager::class);

        $this->type = new FileConfigType($configManager);
    }

    public function testInterface(): void
    {
        $this->assertSame('oro_attachment_file_config', $this->type->getName());
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT);

        $options = [];
        $this->type->buildForm($builder, $options);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['mapped' => false, 'label' => false]);

        $this->type->configureOptions($resolver);
    }
}
