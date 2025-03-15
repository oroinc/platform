<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OwnerFormSubscriberTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private string $fieldName = 'owner';
    private string $fieldLabel = 'Owner';
    private User $defaultOwner;
    private OwnerFormSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->defaultOwner = new User();

        $isAssignGranted = true;
        $this->subscriber = new OwnerFormSubscriber(
            $this->doctrine,
            $this->fieldName,
            $this->fieldLabel,
            $isAssignGranted,
            $this->defaultOwner
        );
    }

    public function testGetSubscribedEvents()
    {
        $expectedEvents = [FormEvents::POST_SET_DATA => 'postSetData'];
        $this->assertEquals($expectedEvents, $this->subscriber->getSubscribedEvents());
    }

    public function testPostSetDataNotRootForm()
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);

        $event = new FormEvent($form, null);
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataNoOwnerField()
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(false);
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $event = new FormEvent($form, new \DateTime());
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataNotAnObject()
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(true);
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $event = new FormEvent($form, [1, 2, 3]);
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataNotManagedObject()
    {
        $data = new \DateTime();

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(true);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($data))
            ->willReturn(null);

        $event = new FormEvent($form, $data);
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataReplaceOwnerAssignGranted()
    {
        $data = new Tag();

        $this->prepareEntityManager($data);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(true);
        $form->expects($this->never())
            ->method('get');

        $event = new FormEvent($form, $data);
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataReplaceOwnerAssignNotGranted()
    {
        $data = new Tag();
        $ownerName = 'user';
        $owner = new User();
        $owner->setUsername($ownerName);

        $this->prepareEntityManager($data);

        $ownerForm = $this->createMock(Form::class);
        $ownerForm->expects($this->once())
            ->method('getData')
            ->willReturn($owner);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with($this->fieldName)
            ->willReturn($ownerForm);
        $form->expects($this->once())
            ->method('remove')
            ->with($this->fieldName);
        $form->expects($this->once())
            ->method('add')
            ->with(
                $this->fieldName,
                TextType::class,
                [
                    'disabled' => true,
                    'data' => $ownerName,
                    'mapped' => false,
                    'required' => false,
                    'label' => $this->fieldLabel
                ]
            );

        $isAssignGranted = false;
        $this->subscriber = new OwnerFormSubscriber(
            $this->doctrine,
            $this->fieldName,
            $this->fieldLabel,
            $isAssignGranted, // assign is not granted
            $this->defaultOwner
        );

        $event = new FormEvent($form, $data);
        $this->subscriber->postSetData($event);
    }

    private function prepareEntityManager($entity)
    {
        $entityClass = ClassUtils::getClass($entity);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn([1]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($entityManager);
    }

    public function testPostSetDataSetPredefinedOwnerExists()
    {
        $ownerForm = $this->createMock(Form::class);
        $ownerForm->expects($this->once())
            ->method('getData')
            ->willReturn(new User());
        $ownerForm->expects($this->never())
            ->method('setData');

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with($this->fieldName)
            ->willReturn($ownerForm);

        $event = new FormEvent($form, null);
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataSetPredefinedOwnerNotExists()
    {
        $ownerForm = $this->createMock(Form::class);
        $ownerForm->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $ownerForm->expects($this->once())
            ->method('setData')
            ->with($this->defaultOwner);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with($this->fieldName)
            ->willReturn($ownerForm);

        $event = new FormEvent($form, null);
        $this->subscriber->postSetData($event);
    }
}
