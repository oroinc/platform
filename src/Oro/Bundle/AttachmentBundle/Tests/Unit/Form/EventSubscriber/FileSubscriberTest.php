<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment;
use Symfony\Component\Form\FormEvents;

class FileSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileSubscriber */
    protected $subscriber;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    public function setUp()
    {
        $this->validator = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new FileSubscriber($this->validator);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSetData',
                FormEvents::POST_SUBMIT => 'postSubmit'
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider getTestData
     */
    public function testPreSetData($entity, $mustAddedField)
    {
        $this->form->expects($mustAddedField ? $this->once() : $this->never())
            ->method('add');
        $this->form->expects($this->once())
            ->method('remove')
            ->with('owner');
        $formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $formEvent->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($entity));
        $formEvent->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($this->form));
        $this->subscriber->preSetData($formEvent);
    }

    public function getTestData()
    {
        $correctEntity = new TestAttachment();
        $correctEntity->setId(1);
        $correctEntity->setFilename('test.doc');

        $incorrectEntity = new Attachment();

        return [
            'correctEntity' => [$correctEntity, true],
            'incorrectEntity' => [$incorrectEntity, false]
        ];
    }
}
