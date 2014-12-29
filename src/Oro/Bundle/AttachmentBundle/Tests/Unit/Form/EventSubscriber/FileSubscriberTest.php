<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\EventSubscriber;

use Symfony\Component\Form\FormEvents;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment;

class FileSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileSubscriber */
    protected $subscriber;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

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
        if ($mustAddedField) {
            $formConfig = $this->getMockBuilder('Symfony\Component\Form\FormConfigBuilder')
                ->disableOriginalConstructor()
                ->getMock();
            $this->form->expects($this->once())
                ->method('getConfig')
                ->will($this->returnValue($formConfig));
            $formConfig->expects($this->once())
                ->method('getOption')
                ->with('allowDelete')
                ->will($this->returnValue(true));
        }
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

        $incorrectEntity = new File();

        return [
            'correctEntity' => [$correctEntity, true],
            'incorrectEntity' => [$incorrectEntity, false]
        ];
    }

    public function testPostSubmitEmptyFIle()
    {
        $entity = new File();
        $this->form->expects($this->once())
            ->method('has')
            ->with('emptyFile')
            ->will($this->returnValue(true));
        $this->form->expects($this->once())
            ->method('get')
            ->with('emptyFile')
            ->will($this->returnValue($this->form));
        $this->form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(true));
        $formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $formEvent->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($entity));
        $formEvent->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($this->form));
        $this->subscriber->postSubmit($formEvent);

        $this->assertNotNull($entity->getUpdatedAt());
    }
}
