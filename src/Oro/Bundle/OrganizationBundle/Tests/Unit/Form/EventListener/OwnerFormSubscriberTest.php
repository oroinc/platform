<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OwnerFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var string */
    private $fieldName = 'owner';

    /** @var string */
    private $fieldLabel = 'Owner';

    /** @var User */
    private $defaultOwner;

    /** @var OwnerFormSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->defaultOwner = new User();

        $isAssignGranted = true;
        $this->subscriber = new OwnerFormSubscriber(
            $this->doctrineHelper,
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
            ->willReturn(true);
        $form->expects($this->never())
            ->method('has');

        $event = new FormEvent($form, null);
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataNoOwnerField()
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(false);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntity');

        $event = new FormEvent($form, new \DateTime());
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataNotAnObject()
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(false);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntity');

        $event = new FormEvent($form, [1, 2, 3]);
        $this->subscriber->postSetData($event);
    }

    public function testPostSetDataNotManagedObject()
    {
        $data = new \DateTime();

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(false);
        $form->expects($this->once())
            ->method('has')
            ->with($this->fieldName)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with(get_class($data))
            ->willReturn(false);

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
            ->willReturn(false);
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
            ->willReturn(false);
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
            $this->doctrineHelper,
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
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
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
            ->willReturn(false);
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
            ->willReturn(false);
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
