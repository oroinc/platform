<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\Action;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Model\Action\AddActivityTarget;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddActivityTargetTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var EmailActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityManager;

    /** @var AddActivityTarget */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->emailActivityManager = $this->createMock(EmailActivityManager::class);

        $this->action = new AddActivityTarget(
            $this->contextAccessor,
            $this->emailActivityManager,
            $this->createMock(ActivityListChainProvider::class),
            $this->createMock(EntityManager::class)
        );

        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitializeWithNamedOptions()
    {
        $options = [
            'email' => '$.email',
            'target_entity' => '$.target_entity',
            'attribute' => '$.attribute'
        ];

        $this->action->initialize($options);

        $this->assertEquals('$.email', ReflectionUtil::getPropertyValue($this->action, 'activityEntity'));
        $this->assertEquals('$.target_entity', ReflectionUtil::getPropertyValue($this->action, 'targetEntity'));
        $this->assertEquals('$.attribute', ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithArrayOptions()
    {
        $options = [
            '$.email',
            '$.target_entity',
            '$.attribute'
        ];

        $this->action->initialize($options);

        $this->assertEquals('$.email', ReflectionUtil::getPropertyValue($this->action, 'activityEntity'));
        $this->assertEquals('$.target_entity', ReflectionUtil::getPropertyValue($this->action, 'targetEntity'));
        $this->assertEquals('$.attribute', ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithNamedOptionsAndMissingAttribute()
    {
        $options = [
            'email' => '$.email',
            'target_entity' => '$.target_entity'
        ];

        $this->action->initialize($options);

        $this->assertEquals('$.email', ReflectionUtil::getPropertyValue($this->action, 'activityEntity'));
        $this->assertEquals('$.target_entity', ReflectionUtil::getPropertyValue($this->action, 'targetEntity'));
        $this->assertNull(ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithArrayOptionsAndMissingAttribute()
    {
        $options = [
            '$.email',
            '$.target_entity'
        ];

        $this->action->initialize($options);

        $this->assertEquals('$.email', ReflectionUtil::getPropertyValue($this->action, 'activityEntity'));
        $this->assertEquals('$.target_entity', ReflectionUtil::getPropertyValue($this->action, 'targetEntity'));
        $this->assertNull(ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithMissingRequiredOption()
    {
        $this->expectException(InvalidParameterException::class);

        $this->action->initialize(['email' => '$.email']);
    }

    public function testExecuteActionWithAttribute()
    {
        $options = [
            'email' => '$.email',
            'target_entity' => '$.target_entity',
            'attribute' => '$.attribute'
        ];

        $fakeContext = ['fake', 'things', 'are', 'here'];

        $email = new Email();
        $target = new User();

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [$fakeContext, '$.email', $email],
                [$fakeContext, '$.target_entity', $target]
            ]);
        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with($fakeContext, '$.attribute', true);

        $this->emailActivityManager->expects($this->once())
            ->method('addAssociation')
            ->with($email, $target)
            ->willReturn(true);

        $this->action->initialize($options);
        $this->action->execute($fakeContext);
    }

    public function testExecuteActionWithoutAttribute()
    {
        $options = [
            'email' => '$.email',
            'target_entity' => '$.target_entity'
        ];

        $fakeContext = ['fake', 'things', 'are', 'here'];

        $email = new Email();
        $target = new User();

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [$fakeContext, '$.email', $email],
                [$fakeContext, '$.target_entity', $target]
            ]);
        $this->contextAccessor->expects($this->never())
            ->method('setValue');

        $this->emailActivityManager->expects($this->once())
            ->method('addAssociation')
            ->with($email, $target)
            ->willReturn(true);

        $this->action->initialize($options);
        $this->action->execute($fakeContext);
    }
}
