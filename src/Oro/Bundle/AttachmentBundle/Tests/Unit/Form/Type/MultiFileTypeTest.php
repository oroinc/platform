<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileItemType;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiFileType;
use Oro\Bundle\AttachmentBundle\Provider\MultipleFileConstraintsProvider;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class MultiFileTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventSubscriberInterface */
    private $eventSubscriber;

    /** @var MultiFileType */
    private $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->eventSubscriber = $this->createMock(EventSubscriberInterface::class);
        $multipleFileConstraintsProvider = $this->createMock(MultipleFileConstraintsProvider::class);
        $this->type = new MultiFileType($this->eventSubscriber, $multipleFileConstraintsProvider);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with([
                'entry_type' => FileItemType::class,
                'error_bubbling' => false,
                'constraints' => [
                    new Valid(),
                ],
            ]);

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(MultiFileType::TYPE, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(MultiFileType::TYPE, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with($this->eventSubscriber);

        $this->type->buildForm($builder, []);
    }
}
