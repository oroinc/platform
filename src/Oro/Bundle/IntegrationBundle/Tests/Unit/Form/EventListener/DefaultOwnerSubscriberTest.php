<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultOwnerSubscriber;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\DefaultOwnerTypeAwareInterface;

class DefaultOwnerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var */
    protected $user;

    /** @var TypesRegistry */
    protected $typesRegistry;

    /** @var DefaultOwnerSubscriber */
    protected $subscriber;

    public function setUp()
    {
        $this->user     = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();

        $securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($this->user));

        $this->typesRegistry = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry')
            ->getMock();

        $this->subscriber = new DefaultOwnerSubscriber($securityFacade, $this->typesRegistry);
    }

    public function tearDown()
    {
        unset($this->subscriber);
    }

    /**
     * @dataProvider formDataProvider
     *
     * @param mixed $formData
     * @param bool  $setsUser
     */
    public function testPostSet($formData, $setsUser)
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::POST_SET_DATA, $events);
        $this->assertEquals($events[FormEvents::POST_SET_DATA], 'postSet');

        $form      = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $fieldMock = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->any())
            ->method('has')
            ->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue(true));

        $form->expects($this->any())
            ->method('get')
            ->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue($fieldMock));

        if ($setsUser) {
            $fieldMock->expects($this->once())->method('setData')->with($this->identicalTo($this->user));
        } else {
            $fieldMock->expects($this->never())->method('setData');
        }

        $event = new  FormEvent($form, $formData);
        $this->subscriber->postSet($event);
    }

    /**
     * @return array
     */
    public function formDataProvider()
    {
        $integration = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $integration->expects($this->any())->method('getId')
            ->will($this->returnValue(123));

        return [
            'should set if null value given'        => [null, true],
            'should not set for saved integrations' => [$integration, false]
        ];
    }

    public function testPreSetWithUserDefaultOwnerType()
    {
        $integration = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $integration->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('integration_type'));

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with($this->equalTo('integration_type'))
            ->will($this->returnValue(DefaultOwnerTypeAwareInterface::USER));

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->at(0))
            ->method('has')
            ->with($this->equalTo('defaultBusinessUnitOwner'))
            ->will($this->returnValue(false));

        $form->expects($this->at(1))
            ->method('has')
            ->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue(false));

        $form->expects($this->once())
            ->method('add')
            ->with(
                $this->equalTo('defaultUserOwner'),
                $this->equalTo('oro_user_organization_acl_select'),
                $this->equalTo(
                    [
                        'required' => true,
                        'label'    => 'oro.integration.integration.default_user_owner.label',
                        'tooltip'  => 'oro.integration.integration.default_user_owner.description',
                        'constraints' => new NotBlank()
                    ]
                )
            )
            ->will($this->returnValue(true));

        $event = new  FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithBusinessUnitDefaultOwnerType()
    {
        $integration = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $integration->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('integration_type'));

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with($this->equalTo('integration_type'))
            ->will($this->returnValue(DefaultOwnerTypeAwareInterface::BUSINESS_UNIT));

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->at(0))
            ->method('has')
            ->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue(false));

        $form->expects($this->at(1))
            ->method('has')
            ->with($this->equalTo('defaultBusinessUnitOwner'))
            ->will($this->returnValue(false));

        $form->expects($this->once())
            ->method('add')
            ->with(
                $this->equalTo('defaultBusinessUnitOwner'),
                $this->equalTo('oro_business_unit_select'),
                $this->equalTo(
                    [
                        'required'    => true,
                        'label'       => 'oro.integration.integration.default_business_unit_owner.label',
                        'tooltip'     => 'oro.integration.integration.default_business_unit_owner.description',
                        'constraints' => new NotBlank(),
                    ]
                )
            )
            ->will($this->returnValue(true));


        $event = new FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithUserDefaultOwnerTypeAndExistingOtherField()
    {
        $integration = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $integration->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('integration_type'));

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with($this->equalTo('integration_type'))
            ->will($this->returnValue(DefaultOwnerTypeAwareInterface::USER));

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->at(0))
            ->method('has')
            ->with($this->equalTo('defaultBusinessUnitOwner'))
            ->will($this->returnValue(true));

        $form->expects($this->once())
            ->method('remove')
            ->with($this->equalTo('defaultBusinessUnitOwner'));

        $form->expects($this->at(2))
            ->method('has')
            ->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue(false));

        $form->expects($this->once())
            ->method('add')
            ->with(
                $this->equalTo('defaultUserOwner'),
                $this->equalTo('oro_user_organization_acl_select'),
                $this->equalTo(
                    [
                        'required' => true,
                        'label'    => 'oro.integration.integration.default_user_owner.label',
                        'tooltip'  => 'oro.integration.integration.default_user_owner.description',
                        'constraints' => new NotBlank(),
                    ]
                )
            )
            ->will($this->returnValue(true));

        $event = new  FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithBusinessUnitDefaultOwnerTypeAndExistingOtherField()
    {
        $integration = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $integration->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('integration_type'));

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with($this->equalTo('integration_type'))
            ->will($this->returnValue(DefaultOwnerTypeAwareInterface::BUSINESS_UNIT));

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->at(0))
            ->method('has')
            ->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue(true));

        $form->expects($this->once())
            ->method('remove')
            ->with($this->equalTo('defaultUserOwner'));

        $form->expects($this->at(2))
            ->method('has')
            ->with($this->equalTo('defaultBusinessUnitOwner'))
            ->will($this->returnValue(false));

        $form->expects($this->once())
            ->method('add')
            ->with(
                $this->equalTo('defaultBusinessUnitOwner'),
                $this->equalTo('oro_business_unit_select'),
                $this->equalTo(
                    [
                        'required'    => true,
                        'label'       => 'oro.integration.integration.default_business_unit_owner.label',
                        'tooltip'     => 'oro.integration.integration.default_business_unit_owner.description',
                        'constraints' => new NotBlank(),
                    ]
                )
            )
            ->will($this->returnValue(true));


        $event = new FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithUserDefaultOwnerTypeAndExistingSameField()
    {
        $integration = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $integration->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('integration_type'));

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with($this->equalTo('integration_type'))
            ->will($this->returnValue(DefaultOwnerTypeAwareInterface::USER));

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->at(0))
            ->method('has')
            ->with($this->equalTo('defaultBusinessUnitOwner'))
            ->will($this->returnValue(false));

        $form->expects($this->at(1))
            ->method('has')
            ->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue(true));

        $form->expects($this->never())->method('add');

        $event = new  FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithBusinessUnitDefaultOwnerTypeAndExistingSameField()
    {
        $integration = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $integration->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('integration_type'));

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with($this->equalTo('integration_type'))
            ->will($this->returnValue(DefaultOwnerTypeAwareInterface::BUSINESS_UNIT));

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->at(0))
            ->method('has')
            ->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue(false));

        $form->expects($this->at(1))
            ->method('has')
            ->with($this->equalTo('defaultBusinessUnitOwner'))
            ->will($this->returnValue(true));

        $form->expects($this->never())->method('add');

        $event = new FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }
}
