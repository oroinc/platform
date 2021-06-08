<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use Oro\Bundle\NotificationBundle\Tests\Unit\Fixtures\Entity\EmailHolderTestEntity;
use Oro\Bundle\NotificationBundle\Tests\Unit\Fixtures\Entity\NotEmailHolderTestEntity;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class AdditionalEmailsSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainAdditionalEmailAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $associationProvider;

    /** @var AdditionalEmailsSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->associationProvider = $this->createMock(ChainAdditionalEmailAssociationProvider::class);

        $this->subscriber = new AdditionalEmailsSubscriber($this->associationProvider);
    }

    public function testGetSubscribedEvents()
    {
        $events = AdditionalEmailsSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $events);
    }

    public function testPreSetData()
    {
        $form = $this->createMock(FormInterface::class);
        $recipientListForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $this->associationProvider->expects($this->exactly(3))
            ->method('getAssociations')
            ->willReturnMap([
                [
                    \stdClass::class,
                    [
                        'non_email_holder_entity_field' => [
                            'label'        => 'nonEmailField',
                            'target_class' => NotEmailHolderTestEntity::class
                        ],
                        'email_holder_entity_field'     => [
                            'label'        => 'EmailField',
                            'target_class' => EmailHolderTestEntity::class
                        ]
                    ]
                ],
                [
                    NotEmailHolderTestEntity::class,
                    [
                        'non_email_holder_entity_field1' => [
                            'label'        => 'nonEmailField1',
                            'target_class' => \stdClass::class
                        ],
                        'email_holder_entity_field1'     => [
                            'label'        => 'EmailField1',
                            'target_class' => EmailHolderTestEntity::class
                        ]
                    ]
                ],
                [
                    EmailHolderTestEntity::class,
                    [
                        'email_holder_entity_field2' => [
                            'label'        => 'EmailField2',
                            'target_class' => EmailHolderTestEntity::class
                        ]
                    ]
                ]
            ]);

        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'additionalEmailAssociations',
                ChoiceType::class,
                [
                    'label'    => 'oro.notification.emailnotification.additional_email_associations.label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices'  => [
                        'nonEmailField > EmailField1' => 'non_email_holder_entity_field.email_holder_entity_field1',
                        'EmailField'                  => 'email_holder_entity_field',
                        'EmailField > EmailField2'    => 'email_holder_entity_field.email_holder_entity_field2'
                    ],
                    'tooltip'  => 'oro.notification.emailnotification.additional_associations.tooltip'
                ]
            );

        $notification = new EmailNotification();
        $notification->setEntityName(\stdClass::class);
        $event = new FormEvent($form, $notification);
        $this->subscriber->preSetData($event);
    }

    public function testPreSetDataWithEmptyEventObject()
    {
        $form = $this->createMock(FormInterface::class);
        $recipientListForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $this->associationProvider->expects($this->never())
            ->method('getAssociations');

        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'additionalEmailAssociations',
                ChoiceType::class,
                [
                    'label'    => 'oro.notification.emailnotification.additional_email_associations.label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices'  => [],
                    'tooltip'  => 'oro.notification.emailnotification.additional_associations.tooltip'
                ]
            );

        $event = new FormEvent($form, null);
        $this->subscriber->preSetData($event);
    }

    public function testPreSetDataWithEmptyEntityNameInNotification()
    {
        $form = $this->createMock(FormInterface::class);
        $recipientListForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $this->associationProvider->expects($this->never())
            ->method('getAssociations');

        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'additionalEmailAssociations',
                ChoiceType::class,
                [
                    'label'    => 'oro.notification.emailnotification.additional_email_associations.label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices'  => [],
                    'tooltip'  => 'oro.notification.emailnotification.additional_associations.tooltip'
                ]
            );

        $notification = new EmailNotification();
        $event = new FormEvent($form, $notification);
        $this->subscriber->preSetData($event);
    }

    public function testPreSubmit()
    {
        $form = $this->createMock(FormInterface::class);
        $recipientListForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $this->associationProvider->expects($this->exactly(3))
            ->method('getAssociations')
            ->willReturnMap([
                [
                    \stdClass::class,
                    [
                        'non_email_holder_entity_field' => [
                            'label'        => 'nonEmailField',
                            'target_class' => NotEmailHolderTestEntity::class
                        ],
                        'email_holder_entity_field'     => [
                            'label'        => 'EmailField',
                            'target_class' => EmailHolderTestEntity::class
                        ]
                    ]
                ],
                [
                    NotEmailHolderTestEntity::class,
                    [
                        'non_email_holder_entity_field1' => [
                            'label'        => 'nonEmailField1',
                            'target_class' => \stdClass::class
                        ],
                        'email_holder_entity_field1'     => [
                            'label'        => 'EmailField1',
                            'target_class' => EmailHolderTestEntity::class
                        ]
                    ]
                ],
                [
                    EmailHolderTestEntity::class,
                    [
                        'email_holder_entity_field2' => [
                            'label'        => 'EmailField2',
                            'target_class' => EmailHolderTestEntity::class
                        ]
                    ]
                ]
            ]);

        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'additionalEmailAssociations',
                ChoiceType::class,
                [
                    'label'    => 'oro.notification.emailnotification.additional_email_associations.label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices'  => [
                        'nonEmailField > EmailField1' => 'non_email_holder_entity_field.email_holder_entity_field1',
                        'EmailField'                  => 'email_holder_entity_field',
                        'EmailField > EmailField2'    => 'email_holder_entity_field.email_holder_entity_field2'
                    ],
                    'tooltip'  => 'oro.notification.emailnotification.additional_associations.tooltip'
                ]
            );

        $event = new FormEvent($form, ['entityName' => \stdClass::class]);
        $this->subscriber->preSubmit($event);
    }

    public function testPreSubmitWithEmptyData()
    {
        $form = $this->createMock(FormInterface::class);
        $recipientListForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $this->associationProvider->expects($this->never())
            ->method('getAssociations');

        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'additionalEmailAssociations',
                ChoiceType::class,
                [
                    'label'    => 'oro.notification.emailnotification.additional_email_associations.label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices'  => [],
                    'tooltip'  => 'oro.notification.emailnotification.additional_associations.tooltip'
                ]
            );

        $event = new FormEvent($form, []);
        $this->subscriber->preSubmit($event);
    }
}
