<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TagBundle\Form\EventSubscriber\TagSubscriber;
use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;
use Oro\Bundle\TagBundle\Form\Type\TagSelectType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TagSelectTypeTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TagTransformer&MockObject $transformer;
    private TagSubscriber&MockObject $subscriber;
    private TagSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->transformer = $this->createMock(TagTransformer::class);
        $this->subscriber = $this->createMock(TagSubscriber::class);

        $this->type = new TagSelectType($this->authorizationChecker, $this->transformer, $this->subscriber);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturnSelf();

        $builder->expects($this->any())
            ->method('add')
            ->willReturnSelf();

        $builder->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }
}
