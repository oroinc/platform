<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AdditionalEmailsSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
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
        $form = $this->createMock(FormInterface::class);
        $notification = $this->createMock(EmailNotification::class);
        $this->assertInitAdditionalRecipientChoicesCalls($form, $notification);

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
        $form = $this->createMock(FormInterface::class);
        $notification = $this->createMock(EmailNotification::class);
        $this->assertInitAdditionalRecipientChoicesCalls($form, $notification);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(['entityName' => RecipientList::class]);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->subscriber->preSubmit($event);
    }

    public function testGetSubscribedEvents()
    {
        $events = AdditionalEmailsSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $events);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param \PHPUnit\Framework\MockObject\MockObject $form
     * @param \PHPUnit\Framework\MockObject\MockObject $notification
     */
    protected function assertInitAdditionalRecipientChoicesCalls(
        \PHPUnit\Framework\MockObject\MockObject $form,
        \PHPUnit\Framework\MockObject\MockObject $notification
    ) {
        $recipientListClass = RecipientList::class;
        $groupClass = Group::class;
        $ownerClass = BusinessUnit::class;
        $userClass = User::class;

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
        $manager->expects($this->exactly(3))
            ->method('getClassMetadata')
            ->willReturnMap([
                [$recipientListClass, $recipientListClassMetadata],
                [$groupClass, $groupClassMetadata],
                [$userClass, $userClassMetadata]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

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
                            'Groups > group.owner TRANS' => 'groups.owner',
                            'Users' => 'users',
                        ];

                        return $options['choices'] == $expectedChoices;
                    }
                )
            );

        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $this->configManager->expects($this->exactly(3))
            ->method('hasConfig')
            ->willReturnMap([
                [$recipientListClass, 'groups', false],
                [$groupClass, 'owner', true],
                [$recipientListClass, 'users', false],
            ]);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn('group.owner');

        $provider = $this->createMock(ConfigProvider::class);
        $provider->expects($this->once())
            ->method('getConfig')
            ->with($groupClass, 'owner')
            ->willReturn($fieldConfig);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($provider);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($str) {
                return $str . ' TRANS';
            });
    }
}
