<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\EventListener\MultipleEntitySubscriber;
use Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener\Stub\ChildEntity;
use Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener\Stub\ParentEntity;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class MultipleEntitySubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::POST_SET_DATA => 'postSet',
                FormEvents::POST_SUBMIT => 'postSubmit'
            ],
            MultipleEntitySubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider postSetDataProvider
     */
    public function testPostSetData(?Collection $data, array $expectedAddedData, array $expectedRemovedData)
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $subscriber = new MultipleEntitySubscriber($doctrineHelper);

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);

        $formAdded = $this->createMock(FormInterface::class);
        $formAdded->expects($this->once())
            ->method('setData')
            ->with($expectedAddedData);
        $formRemoved = $this->createMock(FormInterface::class);
        $formRemoved->expects($this->once())
            ->method('setData')
            ->with($expectedRemovedData);

        $map = [['added', $formAdded], ['removed', $formRemoved]];
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap($map);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $subscriber->postSet($event);
    }

    public function postSetDataProvider(): array
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->createMock(UnitOfWork::class));
        $meta = $this->createMock(ClassMetadata::class);

        $existing = (object)['$existing' => true];
        $removed = (object)['$removed' => true];
        $added = (object)['$added' => true];

        $collectionWithElements = new ArrayCollection([$added]);

        $cleanCollection = new PersistentCollection($em, $meta, new ArrayCollection());

        $dirtyCollection = new PersistentCollection($em, $meta, new ArrayCollection([$existing, $removed]));
        $dirtyCollection->takeSnapshot();
        $dirtyCollection->removeElement($removed);
        $dirtyCollection->add($added);

        return [
            'Initialization with empty value should not be broken'         => [
                '$data'                => null,
                '$expectedAddedData'   => [],
                '$expectedRemovedData' => [],
            ],
            'Empty collection given should set nothing'                    => [
                '$data'                => new ArrayCollection(),
                '$expectedAddedData'   => [],
                '$expectedRemovedData' => [],
            ],
            'Array collection with elements given, should be set to added' => [
                '$data'                => $collectionWithElements,
                '$expectedAddedData'   => [$added],
                '$expectedRemovedData' => [],
            ],
            'Clean persistent collection given, should set nothing'        => [
                '$data'                => $cleanCollection,
                '$expectedAddedData'   => [],
                '$expectedRemovedData' => [],
            ],
            'Persistent collection given, should set from diffs'           => [
                '$data'                => $dirtyCollection,
                '$expectedAddedData'   => [$added],
                '$expectedRemovedData' => [$removed],
            ],
        ];
    }

    public function testPostSubmitForNotEntity()
    {
        $fieldName = 'children';

        $parent = new ParentEntity();
        $existing = new ChildEntity('existing');
        $existing->setParent($parent);
        $added = new ChildEntity('added');
        $removed = new ChildEntity('removed');
        $removed->setParent($parent);
        $children = new ArrayCollection([$existing, $removed]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn(null);
        $subscriber = new MultipleEntitySubscriber($doctrineHelper);

        $form = $this->getPostSubmitForm($parent, $children, [$added], [$removed]);
        $form->expects($this->once())
            ->method('getName')
            ->willReturn($fieldName);

        $event = new FormEvent($form, null);
        $subscriber->postSubmit($event);

        $this->assertEquals([$existing, $added], array_values($children->toArray()));
        $this->assertSame($parent, $existing->getParent());
        $this->assertNull($added->getParent());
        $this->assertSame($parent, $removed->getParent());
    }

    public function testPostSubmitForManyToMany()
    {
        $fieldName = 'children';

        $parent = new ParentEntity();
        $existing = new ChildEntity('existing');
        $existing->setParent($parent);
        $added = new ChildEntity('added');
        $removed = new ChildEntity('removed');
        $removed->setParent($parent);
        $children = new ArrayCollection([$existing, $removed]);

        $parentMetadata = $this->createMock(ClassMetadata::class);
        $parentMetadata->expects($this->any())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(true);
        $parentMetadata->expects($this->any())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->willReturn(
                [
                    'type'         => ClassMetadata::MANY_TO_MANY,
                    'targetEntity' => get_class($existing)
                ]
            );

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with(get_class($parent))
            ->willReturn($parentMetadata);
        $subscriber = new MultipleEntitySubscriber($doctrineHelper);

        // Adding $existing and $added entities
        $form = $this->getPostSubmitForm($parent, $children, [$existing, $added], [$removed]);
        $form->expects($this->once())
            ->method('getName')
            ->willReturn($fieldName);

        $event = new FormEvent($form, null);
        $subscriber->postSubmit($event);

        $this->assertEquals([$existing, $added], array_values($children->toArray()));
        $this->assertSame($parent, $existing->getParent());
        $this->assertNull($added->getParent());
        $this->assertSame($parent, $removed->getParent());
    }

    public function testPostSubmitForManyToManyWithoutParentData()
    {
        $fieldName = 'children';

        $existing = new ChildEntity('existing');
        $added = new ChildEntity('added');
        $removed = new ChildEntity('removed');
        $children = new ArrayCollection([$existing, $removed]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->never())
            ->method('getEntityMetadata');
        $subscriber = new MultipleEntitySubscriber($doctrineHelper);

        $form = $this->getPostSubmitForm(null, $children, [$added], [$removed]);
        $form->expects($this->once())
            ->method('getName')
            ->willReturn($fieldName);

        $event = new FormEvent($form, null);
        $subscriber->postSubmit($event);

        $this->assertEquals([$existing, $added], array_values($children->toArray()));
        $this->assertNull($added->getParent());
    }

    public function testPostSubmitForOneToMany()
    {
        $fieldName = 'children';

        $parent = new ParentEntity();
        $existing = new ChildEntity('existing');
        $existing->setParent($parent);
        $added = new ChildEntity('added');
        $removed = new ChildEntity('removed');
        $removed->setParent($parent);
        $children = new ArrayCollection([$existing, $removed]);

        $parentMetadata = $this->createMock(ClassMetadata::class);
        $parentMetadata->expects($this->any())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(true);
        $parentMetadata->expects($this->any())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->willReturn(
                [
                    'type'         => ClassMetadata::ONE_TO_MANY,
                    'targetEntity' => get_class($existing),
                    'mappedBy'     => 'parent'
                ]
            );

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with(get_class($parent))
            ->willReturn($parentMetadata);
        $subscriber = new MultipleEntitySubscriber($doctrineHelper);

        $form = $this->getPostSubmitForm($parent, $children, [$added], [$removed]);
        $form->expects($this->once())
            ->method('getName')
            ->willReturn($fieldName);

        $event = new FormEvent($form, null);
        $subscriber->postSubmit($event);

        $this->assertEquals([$existing, $added], array_values($children->toArray()));
        $this->assertSame($parent, $existing->getParent());
        $this->assertSame($parent, $added->getParent());
        $this->assertNull($removed->getParent());
    }

    private function getPostSubmitForm(
        ?object $parent,
        Collection $children,
        array $added,
        array $removed
    ): FormInterface|\PHPUnit\Framework\MockObject\MockObject {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $parentForm = $this->createMock(FormInterface::class);

        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formAdded = $this->createMock(FormInterface::class);
        $formAdded->expects($this->once())
            ->method('getData')
            ->willReturn($added);
        $formRemoved = $this->createMock(FormInterface::class);
        $formRemoved->expects($this->once())
            ->method('getData')
            ->willReturn($removed);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['added', $formAdded],
                ['removed', $formRemoved]
            ]);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($children);
        $parentForm->expects($this->any())
            ->method('getData')
            ->willReturn($parent);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parentForm);

        return $form;
    }
}
