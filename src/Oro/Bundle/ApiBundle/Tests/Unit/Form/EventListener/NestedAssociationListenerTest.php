<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\EventListener\NestedAssociationListener;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Unit\Form\EventListener\Fixtures\ObjectWithNestedAssociation;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class NestedAssociationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NestedAssociationListener */
    private $listener;

    protected function setUp()
    {
        $config = new EntityDefinitionFieldConfig();
        $targetConfig = $config->createAndSetTargetEntity();
        $targetConfig->addField('__class__')->setPropertyPath('relatedObjectClassName');
        $targetConfig->addField('id')->setPropertyPath('relatedObjectId');

        $this->listener = new NestedAssociationListener(new PropertyAccessor(), $config);
    }

    /**
     * @param mixed $entity
     * @param mixed $data
     *
     * @return FormEvent
     */
    private function getFormEvent($entity, $data)
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

    public function testGetSubscribedEvents()
    {
        self::assertEquals(
            [
                FormEvents::POST_SUBMIT => 'postSubmit'
            ],
            NestedAssociationListener::getSubscribedEvents()
        );
    }

    public function testShouldSetRelatedFieldsOnPostSubmit()
    {
        $entity = new ObjectWithNestedAssociation();

        $this->listener->postSubmit(
            $this->getFormEvent($entity, new EntityIdentifier(123, 'Test\Entity'))
        );

        self::assertEquals('Test\Entity', $entity->relatedObjectClassName);
        self::assertEquals(123, $entity->relatedObjectId);
    }

    public function testPostSubmitWithNullData()
    {
        $entity = new ObjectWithNestedAssociation();

        $this->listener->postSubmit(
            $this->getFormEvent($entity, null)
        );

        self::assertNull($entity->relatedObjectClassName);
        self::assertNull($entity->relatedObjectId);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected argument of type "Oro\Bundle\ApiBundle\Model\EntityIdentifier", "string" given.
     */
    // @codingStandardsIgnoreEnd
    public function testPostSubmitWithUnexpectedData()
    {
        $entity = new ObjectWithNestedAssociation();

        $this->listener->postSubmit(
            $this->getFormEvent($entity, 'test')
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected argument of type "Oro\Bundle\ApiBundle\Model\EntityIdentifier", "stdClass" given.
     */
    // @codingStandardsIgnoreEnd
    public function testPostSubmitWithUnexpectedObjectData()
    {
        $entity = new ObjectWithNestedAssociation();

        $this->listener->postSubmit(
            $this->getFormEvent($entity, new \stdClass())
        );
    }
}
