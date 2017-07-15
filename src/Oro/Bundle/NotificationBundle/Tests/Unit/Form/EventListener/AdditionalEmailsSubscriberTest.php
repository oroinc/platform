<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;

class AdditionalEmailsSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var AdditionalEmailsSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->subscriber = new AdditionalEmailsSubscriber($this->registry, $this->translator, $this->configManager);
    }

    public function testPreSetData()
    {
        $recipientListClass = 'Oro\Bundle\NotificationBundle\Entity\RecipientList';
        $groupClass = 'Oro\Bundle\UserBundle\Entity\Group';
        $ownerClass = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';
        $userClass = 'Oro\Bundle\UserBundle\Entity\User';

        $recipientListClassMetadata = $this->createMock(ClassMetadata::class);
        $recipientListClassMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'groups' => ['targetEntity' => $groupClass],
                'users' => ['targetEntity' => $userClass],
            ]);

        $groupClassMetadata = $this->createMock(ClassMetadata::class);
        $groupClassMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'owner' => ['targetEntity' => $ownerClass],
            ]);

        $userClassMetadata = $this->createMock(ClassMetadata::class);
        $userClassMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->at(0))
            ->method('getClassMetadata')
            ->with($recipientListClass)
            ->willReturn($recipientListClassMetadata);
        $manager->expects($this->at(1))
            ->method('getClassMetadata')
            ->with($groupClass)
            ->willReturn($groupClassMetadata);
        $manager->expects($this->at(2))
            ->method('getClassMetadata')
            ->with($userClass)
            ->willReturn($userClassMetadata);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $notification = $this->createMock(EmailNotification::class);
        $notification->expects($this->any())
            ->method('getEntityName')
            ->willReturn($recipientListClass);
        $notification->expects($this->any())
            ->method('hasEntityName')
            ->willReturn(true);

        $recipientListForm = $this->createMock(FormInterface::class);
        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'additionalEmailAssociations',
                $this->anything(),
                $this->callback(
                    function ($options) {
                        $expectedChoices = [
                            'groups.owner' => 'Groups > Owner',
                            'users' => 'Users',
                        ];
                        return $options['choices'] == $expectedChoices;
                    }
                )
            );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($notification);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->subscriber->preSetData($event);
    }

    public function testPreSubmitData()
    {
        $recipientListClass = 'Oro\Bundle\NotificationBundle\Entity\RecipientList';
        $groupClass = 'Oro\Bundle\UserBundle\Entity\Group';
        $ownerClass = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';
        $userClass = 'Oro\Bundle\UserBundle\Entity\User';

        $recipientListClassMetadata = $this->createMock(ClassMetadata::class);
        $recipientListClassMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'groups' => ['targetEntity' => $groupClass],
                'users' => ['targetEntity' => $userClass],
            ]);

        $groupClassMetadata = $this->createMock(ClassMetadata::class);
        $groupClassMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'owner' => ['targetEntity' => $ownerClass],
            ]);

        $userClassMetadata = $this->createMock(ClassMetadata::class);
        $userClassMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->at(0))
            ->method('getClassMetadata')
            ->with($recipientListClass)
            ->willReturn($recipientListClassMetadata);
        $manager->expects($this->at(1))
            ->method('getClassMetadata')
            ->with($groupClass)
            ->willReturn($groupClassMetadata);
        $manager->expects($this->at(2))
            ->method('getClassMetadata')
            ->with($userClass)
            ->willReturn($userClassMetadata);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $notification = $this->createMock(EmailNotification::class);
        $notification->expects($this->any())
            ->method('getEntityName')
            ->willReturn($recipientListClass);
        $notification->expects($this->any())
            ->method('hasEntityName')
            ->willReturn(true);

        $recipientListForm = $this->createMock(FormInterface::class);
        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'additionalEmailAssociations',
                $this->anything(),
                $this->callback(
                    function ($options) {
                        $expectedChoices = [
                            'groups.owner' => 'Groups > Owner',
                            'users' => 'Users',
                        ];
                        return $options['choices'] == $expectedChoices;
                    }
                )
            );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(['entityName' => $recipientListClass]);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->subscriber->preSubmit($event);
    }
}
