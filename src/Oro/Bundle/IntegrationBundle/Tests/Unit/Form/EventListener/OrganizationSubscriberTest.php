<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\IntegrationBundle\Form\EventListener\OrganizationSubscriber;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class OrganizationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectManager */
    protected $objectManager;

    /** @var OrganizationSubscriber */
    protected $subscriber;

    /** @var Organization */
    protected $organization;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formData;

    public function setUp()
    {
        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();

        $this->organization = new Organization();

        $repository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();

        $repository->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue([$this->organization]));

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->subscriber = new OrganizationSubscriber($this->objectManager);

        $this->formData = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
    }

    /**
     * @dataProvider formDataProvider
     *
     * @param Channel|null $formData
     */
    public function testPostSet($formData)
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::POST_SET_DATA, $events);
        $this->assertEquals($events[FormEvents::POST_SET_DATA], 'postSet');

        $form      = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $fieldMock = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $fieldMock->expects($this->once())->method('setData');

        $form->expects($this->once())->method('get')->with($this->equalTo('organization'))
            ->will($this->returnValue($fieldMock));

        $form->expects($this->at(0))->method('has')->with($this->equalTo('organization'))
            ->will($this->returnValue(false));

        $form->expects($this->at(2))->method('has')->with($this->equalTo('organization'))
            ->will($this->returnValue(true));

        if (!empty($formData)) {
            $formData->expects($this->once())->method('getOrganization');
        }

        $event = new FormEvent($form, $formData);
        $this->subscriber->postSet($event);
    }

    /**
     * @return array
     */
    public function formDataProvider()
    {
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $channel->expects($this->any())->method('getId')
            ->will($this->returnValue(123));

        return [
            'without data' => [null],
            'with data'    => [$channel]
        ];
    }
}
