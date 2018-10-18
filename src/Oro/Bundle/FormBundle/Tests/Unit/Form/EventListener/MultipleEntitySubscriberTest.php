<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\FormBundle\Form\EventListener\MultipleEntitySubscriber;
use Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener\Stub\ChildEntity;
use Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener\Stub\ParentEntity;
use Oro\Component\TestUtils\ORM\Mocks\UnitOfWork;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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
     *
     * @param Collection $data
     * @param array      $expectedAddedData
     * @param array      $expectedRemovedData
     */
    public function testPostSetData($data, $expectedAddedData, $expectedRemovedData)
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $subscriber = new MultipleEntitySubscriber($doctrineHelper);

        $form  = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $event = new FormEvent($form, null);

        $formAdded = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $formAdded->expects($this->once())->method('setData')->with($expectedAddedData);
        $formRemoved = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $formRemoved->expects($this->once())->method('setData')->with($expectedRemovedData);

        $map = [['added', $formAdded], ['removed', $formRemoved]];
        $form->expects($this->any())->method('get')->willReturnMap($map);
        $form->expects($this->any())->method('getData')->willReturn($data);

        $subscriber->postSet($event);
    }

    /**
     * @return array
     */
    public function postSetDataProvider()
    {
        $uow = new UnitOfWork();
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $meta = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');

        $existing = (object)['$existing' => true];
        $removed  = (object)['$removed' => true];
        $added    = (object)['$added' => true];

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

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
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

        $parentMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
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

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
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
        $this->assertNull($added->getParent());
        $this->assertSame($parent, $removed->getParent());
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

        $parentMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
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

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
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

    /**
     * @param object     $parent
     * @param Collection $children
     * @param object[]   $added
     * @param object[]   $removed
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Form\Test\FormInterface
     */
    protected function getPostSubmitForm(
        $parent,
        Collection $children,
        array $added,
        array $removed
    ) {
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $formConfig = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $parentForm = $this->createMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formAdded = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $formAdded->expects($this->once())
            ->method('getData')
            ->willReturn($added);
        $formRemoved = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $formRemoved->expects($this->once())
            ->method('getData')
            ->willReturn($removed);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['added', $formAdded],
                    ['removed', $formRemoved]
                ]
            );
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
