<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Resolver;

use Oro\Bundle\FormBundle\Resolver\EntityFormResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class EntityFormResolverTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;

    private FormInterface&MockObject $form;

    private EntityFormResolver $entityFormResolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->entityFormResolver = new EntityFormResolver($this->formFactory);
    }

    public function testResolveSuccessfully(): void
    {
        $formTypeClass = 'SomeFormType';
        $entity = new \StdClass();
        $entityData = ['field' => 'value'];

        $this->formFactory
            ->expects(self::once())
            ->method('create')
            ->with($formTypeClass, $entity)
            ->willReturn($this->form);

        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with($entityData);

        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($entity);

        $this->entityFormResolver->resolve($formTypeClass, $entity, $entityData);

        //ensure entity object cached
        $result = $this->entityFormResolver->resolve($formTypeClass, $entity, $entityData);

        self::assertSame($entity, $result);
    }
}
