<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\EventListener\NestedAssociationListener;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Unit\Form\EventListener\Fixtures\ObjectWithNestedAssociation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class NestedAssociationListenerTest extends TestCase
{
    private NestedAssociationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $config = new EntityDefinitionFieldConfig();
        $targetConfig = $config->createAndSetTargetEntity();
        $targetConfig->addField('__class__')->setPropertyPath('relatedObjectClassName');
        $targetConfig->addField('id')->setPropertyPath('relatedObjectId');

        $this->listener = new NestedAssociationListener(PropertyAccess::createPropertyAccessor(), $config);
    }

    private function getFormEvent(object $entity, mixed $data): FormEvent
    {
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->expects(self::once())
            ->method('getData')
            ->willReturn($entity);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($data);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);

        return new FormEvent($form, null);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::POST_SUBMIT => 'postSubmit'
            ],
            NestedAssociationListener::getSubscribedEvents()
        );
    }

    public function testShouldSetRelatedFieldsOnPostSubmit(): void
    {
        $entity = new ObjectWithNestedAssociation();

        $this->listener->postSubmit(
            $this->getFormEvent($entity, new EntityIdentifier(123, 'Test\Entity'))
        );

        self::assertEquals('Test\Entity', $entity->relatedObjectClassName);
        self::assertEquals(123, $entity->relatedObjectId);
    }

    public function testPostSubmitWithNullData(): void
    {
        $entity = new ObjectWithNestedAssociation();

        $this->listener->postSubmit(
            $this->getFormEvent($entity, null)
        );

        self::assertNull($entity->relatedObjectClassName);
        self::assertNull($entity->relatedObjectId);
    }

    public function testPostSubmitWithUnexpectedData(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ApiBundle\Model\EntityIdentifier", "string" given.'
        );

        $entity = new ObjectWithNestedAssociation();

        $this->listener->postSubmit(
            $this->getFormEvent($entity, 'test')
        );
    }

    public function testPostSubmitWithUnexpectedObjectData(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ApiBundle\Model\EntityIdentifier", "stdClass" given.'
        );

        $entity = new ObjectWithNestedAssociation();

        $this->listener->postSubmit(
            $this->getFormEvent($entity, new \stdClass())
        );
    }
}
