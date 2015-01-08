<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\EventListener;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvent;

use Oro\Bundle\FormBundle\Form\EventListener\MultipleEntitySubscriber;

class MultipleEntitySubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testSubscribedEvents()
    {
        $this->assertEquals(
            [
                'form.post_set_data' => 'postSet',
                'form.post_bind'     => 'postSubmit'
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
        $subscriber = new MultipleEntitySubscriber();

        $form  = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $event = new FormEvent($form, null);

        $formAdded = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $formAdded->expects($this->once())->method('setData')->with($expectedAddedData);
        $formRemoved = $this->getMock('Symfony\Component\Form\Test\FormInterface');
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
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $meta = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');

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

    public function testPostSubmit()
    {
        $subscriber = new MultipleEntitySubscriber();

        $form  = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $event = new FormEvent($form, null);

        $existing = (object)['$existing' => true];
        $removed  = (object)['$removed' => true];
        $added    = (object)['$added' => true];

        $collection = new ArrayCollection([$existing, $removed]);

        $formAdded = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $formAdded->expects($this->once())->method('getData')->willReturn([$added]);
        $formRemoved = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $formRemoved->expects($this->once())->method('getData')->willReturn([$removed]);

        $map = [['added', $formAdded], ['removed', $formRemoved]];
        $form->expects($this->any())->method('get')->willReturnMap($map);
        $form->expects($this->any())->method('getData')->willReturn($collection);

        $subscriber->postSubmit($event);

        $this->assertEquals([$existing, $added], array_values($collection->toArray()));
    }
}
