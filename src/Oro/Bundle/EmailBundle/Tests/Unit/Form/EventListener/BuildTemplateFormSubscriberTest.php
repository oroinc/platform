<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BuildTemplateFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var BuildTemplateFormSubscriber */
    private $listener;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->listener = new BuildTemplateFormSubscriber($this->tokenStorage);
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->listener->getSubscribedEvents();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $result);
    }

    public function testPreSetDataEmptyData()
    {
        $eventMock = $this->createMock(FormEvent::class);

        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $eventMock->expects($this->never())
            ->method('getForm');

        $this->listener->preSetData($eventMock);
    }

    public function testPreSetDataEmptyEntityName()
    {
        $eventMock = $this->createMock(FormEvent::class);

        $notificationMock = $this->createMock(EmailNotification::class);
        $notificationMock->expects($this->once())
            ->method('getEntityName')
            ->willReturn(null);

        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn($notificationMock);

        $this->listener->preSetData($eventMock);
    }

    public function testPreSetDataHasTemplates()
    {
        $organization = $this->createMock(Organization::class);
        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $eventMock = $this->createMock(FormEvent::class);

        $notificationMock = $this->createMock(EmailNotification::class);
        $notificationMock->expects($this->exactly(2))
            ->method('getEntityName')
            ->willReturn('testEntity');

        $configMock = $this->createMock(FormConfigInterface::class);
        $configMock->expects($this->once())
            ->method('getOptions')
            ->willReturn(['auto_initialize' => true]);

        $formType = $this->createMock(ResolvedFormTypeInterface::class);
        $configMock->expects($this->once())
            ->method('getType')
            ->willReturn($formType);
        $formType->expects($this->once())
            ->method('getInnerType')
            ->willReturn(new SubmitType());

        $fieldMock = $this->createMock(FormInterface::class);

        $formMock = $this->createMock(FormInterface::class);
        $formMock->expects($this->any())
            ->method('get')
            ->with('template')
            ->willReturn($fieldMock);
        $formMock->expects($this->once())
            ->method('add');

        $fieldMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($configMock);

        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn($notificationMock);
        $eventMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);

        $this->listener->preSetData($eventMock);
    }

    public function testPreSubmitData()
    {
        $organization = $this->createMock(Organization::class);
        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $eventMock = $this->createMock(FormEvent::class);

        $configMock = $this->createMock(FormConfigInterface::class);
        $configMock->expects($this->once())
            ->method('getOptions')
            ->willReturn(['auto_initialize' => true]);

        $formType   = $this->createMock(ResolvedFormTypeInterface::class);
        $configMock->expects($this->once())
            ->method('getType')
            ->willReturn($formType);
        $formType->expects($this->once())
            ->method('getInnerType')
            ->willReturn(new SubmitType());

        $fieldMock = $this->createMock(FormInterface::class);
        $fieldMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($configMock);

        $formMock = $this->createMock(FormInterface::class);
        $formMock->expects($this->any())
            ->method('get')
            ->with('template')
            ->willReturn($fieldMock);
        $formMock->expects($this->once())
            ->method('add');

        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn(['entityName' => 'testEntityName']);
        $eventMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);

        $this->listener->preSubmit($eventMock);
    }
}
