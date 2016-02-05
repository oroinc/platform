<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\EventSubscriber;

use Symfony\Component\Form\FormEvents;

use Oro\Bundle\TagBundle\Form\EventSubscriber\TagSubscriber;

class TagSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const TEST_TAG_NAME = 'testName';

    /** @var TagSubscriber */
    protected $subscriber;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $taggableHelper;

    protected function setUp()
    {
        $this->taggableHelper = $this->getMockBuilder('Oro\Bundle\TagBundle\Helper\TaggableHelper')
            ->disableOriginalConstructor()->getMock();
        $this->manager = $this->getMockBuilder('Oro\Bundle\TagBundle\Entity\TagManager')
            ->disableOriginalConstructor()->getMock();

        $this->subscriber = new TagSubscriber($this->manager, $this->taggableHelper);
    }

    protected function tearDown()
    {
        unset($this->subscriber);
        unset($this->taggableHelper);
        unset($this->manager);
    }

    public function testSubscribedEvents()
    {
        $result = TagSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);

        $this->assertEquals('preSet', $result[FormEvents::PRE_SET_DATA]);
    }

    /**
     * @dataProvider entityProvider
     */
    public function testPreSet($entity, $shouldSetData)
    {
        $eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()->getMock();

        $parentFormMock = $this->getMockForAbstractClass('Symfony\Component\Form\Test\FormInterface');
        $parentFormMock->expects($this->once())->method('getData')
            ->will($this->returnValue($entity));

        $formMock = $this->getMockForAbstractClass('Symfony\Component\Form\Test\FormInterface');
        $formMock->expects($this->once())->method('getParent')
            ->will($this->returnValue($parentFormMock));

        $eventMock->expects($this->once())->method('getForm')
            ->will($this->returnValue($formMock));

        if ($shouldSetData) {
            $this->taggableHelper->expects($this->once())->method('isTaggable')->willReturn(true);
            $this->manager->expects($this->once())->method('getPreparedArray')
                ->with($entity)->will(
                    $this->returnValue(
                        array(array('owner' => false), array('owner' => true))
                    )
                );

            $eventMock->expects($this->exactly($shouldSetData))->method('setData');
        } else {
            $this->manager->expects($this->never())->method('getPreparedArray');
            $eventMock->expects($this->never())->method('setData');
        }

        $this->subscriber->preSet($eventMock);
    }

    /**
     * @return array
     */
    public function entityProvider()
    {
        return array(
            'instance of taggable' => array($this->getMock('Oro\Bundle\TagBundle\Entity\Taggable'), 1),
            'another entity'       => array($this->getMock('Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Entity'), false),
        );
    }
}
