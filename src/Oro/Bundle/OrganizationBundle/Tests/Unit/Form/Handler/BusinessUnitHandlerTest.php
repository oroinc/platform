<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Form\Handler\BusinessUnitHandler;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BusinessUnitHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var Request */
    private $request;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var OwnerTreeProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownerTreeProvider;

    /** @var BusinessUnit */
    private $entity;

    /** @var BusinessUnitHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->form = $this->createMock(Form::class);
        $this->ownerTreeProvider = $this->createMock(OwnerTreeProviderInterface::class);
        $this->entity  = new BusinessUnit();

        $this->handler = new BusinessUnitHandler(
            $this->form,
            $requestStack,
            $this->manager,
            $this->ownerTreeProvider
        );
    }

    public function testProcessValidData()
    {
        $appendedUser = new User();
        $appendedUser->setId(1);

        $removedUser = new User();
        $removedUser->setId(2);

        $removedUser->addBusinessUnit($this->entity);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $appendForm = $this->createMock(Form::class);
        $appendForm->expects($this->once())
            ->method('getData')
            ->willReturn([$appendedUser]);

        $removeForm = $this->createMock(Form::class);
        $removeForm->expects($this->once())
            ->method('getData')
            ->willReturn([$removedUser]);

        $this->form->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['appendUsers', $appendForm],
                ['removeUsers', $removeForm]
            ]);

        $this->manager->expects($this->exactly(3))
            ->method('persist')
            ->withConsecutive(
                [$appendedUser],
                [$removedUser],
                [$this->entity]
            );
        $this->manager->expects($this->once())
            ->method('flush');

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $this->assertTrue($this->handler->process($this->entity));

        $businessUnits = $appendedUser->getBusinessUnits()->toArray();
        $this->assertCount(1, $businessUnits);
        $this->assertEquals($this->entity, current($businessUnits));
        $this->assertCount(0, $removedUser->getBusinessUnits()->toArray());
    }

    public function testBadMethod()
    {
        $this->request->setMethod('GET');
        $this->assertFalse($this->handler->process($this->entity));
    }
}
