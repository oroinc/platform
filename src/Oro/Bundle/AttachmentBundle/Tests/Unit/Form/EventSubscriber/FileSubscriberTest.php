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

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    public function setUp()
    {
        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\Validator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\UserConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvide')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('getProvider')
            ->with('attachment')
            ->will($this->returnValue($this->attachmentConfigProvider));
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new FileSubscriber($this->validator, $configManager, $this->config);
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

    public function testCheckUpdateEntityByDeleteFile()
    {

    }
}
