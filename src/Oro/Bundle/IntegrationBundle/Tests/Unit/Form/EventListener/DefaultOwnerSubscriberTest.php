<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultOwnerSubscriber;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\DefaultOwnerTypeAwareInterface;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitSelectType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Form\Type\OrganizationUserAclSelectType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultOwnerSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractUser|\PHPUnit\Framework\MockObject\MockObject */
    private $user;

    /** @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $typesRegistry;

    /** @var DefaultOwnerSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->user = $this->createMock(AbstractUser::class);
        $this->typesRegistry = $this->createMock(TypesRegistry::class);

        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);

        $this->subscriber = new DefaultOwnerSubscriber($tokenAccessor, $this->typesRegistry);
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

        $form = $this->createMock(FormInterface::class);
        $field = $this->createMock(FormInterface::class);

        $form->expects($this->any())
            ->method('has')
            ->with('defaultUserOwner')
            ->willReturn(true);

        $form->expects($this->any())
            ->method('get')
            ->with('defaultUserOwner')
            ->willReturn($field);

        if ($setsUser) {
            $field->expects($this->once())
                ->method('setData')
                ->with($this->identicalTo($this->user));
        } else {
            $field->expects($this->never())
                ->method('setData');
        }

        $event = new  FormEvent($form, $formData);
        $this->subscriber->postSet($event);
    }

    public function formDataProvider(): array
    {
        $integration = $this->createMock(Channel::class);
        $integration->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        return [
            'should set if null value given'        => [null, true],
            'should not set for saved integrations' => [$integration, false]
        ];
    }

    public function testPreSetWithUserDefaultOwnerType()
    {
        $integration = $this->createMock(Channel::class);
        $integration->expects($this->any())
            ->method('getType')
            ->willReturn('integration_type');

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with('integration_type')
            ->willReturn(DefaultOwnerTypeAwareInterface::USER);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['defaultBusinessUnitOwner', false],
                ['defaultUserOwner', false]
            ]);
        $form->expects($this->once())
            ->method('add')
            ->with(
                'defaultUserOwner',
                OrganizationUserAclSelectType::class,
                [
                    'required'    => true,
                    'label'       => 'oro.integration.integration.default_user_owner.label',
                    'tooltip'     => 'oro.integration.integration.default_user_owner.description',
                    'constraints' => new NotBlank()
                ]
            )
            ->willReturn(true);

        $event = new  FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithBusinessUnitDefaultOwnerType()
    {
        $integration = $this->createMock(Channel::class);
        $integration->expects($this->any())
            ->method('getType')
            ->willReturn('integration_type');

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with('integration_type')
            ->willReturn(DefaultOwnerTypeAwareInterface::BUSINESS_UNIT);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['defaultBusinessUnitOwner', false],
                ['defaultUserOwner', false]
            ]);
        $form->expects($this->once())
            ->method('add')
            ->with(
                'defaultBusinessUnitOwner',
                BusinessUnitSelectType::class,
                [
                    'required'    => true,
                    'label'       => 'oro.integration.integration.default_business_unit_owner.label',
                    'tooltip'     => 'oro.integration.integration.default_business_unit_owner.description',
                    'constraints' => new NotBlank(),
                ]
            )
            ->willReturn(true);

        $event = new FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithUserDefaultOwnerTypeAndExistingOtherField()
    {
        $integration = $this->createMock(Channel::class);
        $integration->expects($this->any())
            ->method('getType')
            ->willReturn('integration_type');

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with('integration_type')
            ->willReturn(DefaultOwnerTypeAwareInterface::USER);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['defaultBusinessUnitOwner', true],
                ['defaultUserOwner', false]
            ]);
        $form->expects($this->once())
            ->method('remove')
            ->with('defaultBusinessUnitOwner');
        $form->expects($this->once())
            ->method('add')
            ->with(
                'defaultUserOwner',
                OrganizationUserAclSelectType::class,
                [
                    'required'    => true,
                    'label'       => 'oro.integration.integration.default_user_owner.label',
                    'tooltip'     => 'oro.integration.integration.default_user_owner.description',
                    'constraints' => new NotBlank(),
                ]
            )
            ->willReturn(true);

        $event = new  FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithBusinessUnitDefaultOwnerTypeAndExistingOtherField()
    {
        $integration = $this->createMock(Channel::class);
        $integration->expects($this->any())
            ->method('getType')
            ->willReturn('integration_type');

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with('integration_type')
            ->willReturn(DefaultOwnerTypeAwareInterface::BUSINESS_UNIT);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['defaultBusinessUnitOwner', false],
                ['defaultUserOwner', true]
            ]);
        $form->expects($this->once())
            ->method('remove')
            ->with('defaultUserOwner');
        $form->expects($this->once())
            ->method('add')
            ->with(
                'defaultBusinessUnitOwner',
                BusinessUnitSelectType::class,
                [
                    'required'    => true,
                    'label'       => 'oro.integration.integration.default_business_unit_owner.label',
                    'tooltip'     => 'oro.integration.integration.default_business_unit_owner.description',
                    'constraints' => new NotBlank(),
                ]
            )
            ->willReturn(true);

        $event = new FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithUserDefaultOwnerTypeAndExistingSameField()
    {
        $integration = $this->createMock(Channel::class);
        $integration->expects($this->any())
            ->method('getType')
            ->willReturn('integration_type');

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with('integration_type')
            ->willReturn(DefaultOwnerTypeAwareInterface::USER);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['defaultBusinessUnitOwner', false],
                ['defaultUserOwner', true]
            ]);
        $form->expects($this->never())
            ->method('add');

        $event = new  FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithBusinessUnitDefaultOwnerTypeAndExistingSameField()
    {
        $integration = $this->createMock(Channel::class);
        $integration->expects($this->any())
            ->method('getType')
            ->willReturn('integration_type');

        $this->typesRegistry->expects($this->any())
            ->method('getDefaultOwnerType')
            ->with('integration_type')
            ->willReturn(DefaultOwnerTypeAwareInterface::BUSINESS_UNIT);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['defaultBusinessUnitOwner', true],
                ['defaultUserOwner', false]
            ]);
        $form->expects($this->never())
            ->method('add');

        $event = new FormEvent($form, $integration);
        $this->subscriber->preSet($event);
    }
}
