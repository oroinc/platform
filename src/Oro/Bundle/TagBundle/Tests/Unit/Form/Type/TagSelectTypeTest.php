<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TagBundle\Form\EventSubscriber\TagSubscriber;
use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;
use Oro\Bundle\TagBundle\Form\Type\TagSelectType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TagSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TagTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $transformer;

    /** @var TagSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $subscriber;

    /** @var TagSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->transformer = $this->createMock(TagTransformer::class);
        $this->subscriber = $this->createMock(TagSubscriber::class);

        $this->type = new TagSelectType($this->authorizationChecker, $this->transformer, $this->subscriber);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    public function testBuildForm()
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
