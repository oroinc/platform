<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\Action;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
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

    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var AddActivityTarget */
    private $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->activityManager = $this->createMock(ActivityManager::class);

        $this->action = new AddActivityTarget($this->contextAccessor, $this->activityManager);
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitializeWithNamedOptions(): void
    {
        $options = [
            'email' => '$.email',
            'target_entity' => '$.target_entity',
            'attribute' => '$.attribute'
        ];

        $this->action->initialize($options);

        self::assertEquals('$.email', ReflectionUtil::getPropertyValue($this->action, 'activityEntity'));
        self::assertEquals('$.target_entity', ReflectionUtil::getPropertyValue($this->action, 'targetEntity'));
        self::assertEquals('$.attribute', ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithArrayOptions(): void
    {
        $options = [
            '$.email',
            '$.target_entity',
            '$.attribute'
        ];

        $this->action->initialize($options);

        self::assertEquals('$.email', ReflectionUtil::getPropertyValue($this->action, 'activityEntity'));
        self::assertEquals('$.target_entity', ReflectionUtil::getPropertyValue($this->action, 'targetEntity'));
        self::assertEquals('$.attribute', ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithNamedOptionsAndMissingAttribute(): void
    {
        $options = [
            'email' => '$.email',
            'target_entity' => '$.target_entity'
        ];

        $this->action->initialize($options);

        self::assertEquals('$.email', ReflectionUtil::getPropertyValue($this->action, 'activityEntity'));
        self::assertEquals('$.target_entity', ReflectionUtil::getPropertyValue($this->action, 'targetEntity'));
        self::assertNull(ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithArrayOptionsAndMissingAttribute(): void
    {
        $options = [
            '$.email',
            '$.target_entity'
        ];

        $this->action->initialize($options);

        self::assertEquals('$.email', ReflectionUtil::getPropertyValue($this->action, 'activityEntity'));
        self::assertEquals('$.target_entity', ReflectionUtil::getPropertyValue($this->action, 'targetEntity'));
        self::assertNull(ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithMissingRequiredOption(): void
    {
        $this->expectException(InvalidParameterException::class);

        $this->action->initialize(['email' => '$.email']);
    }

    public function testExecuteActionWithAttribute(): void
    {
        $options = [
            'email' => '$.email',
            'target_entity' => '$.target_entity',
            'attribute' => '$.attribute'
        ];

        $fakeContext = ['fake', 'things', 'are', 'here'];

        $email = new Email();
        $target = new User();

        $this->contextAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [$fakeContext, '$.email', $email],
                [$fakeContext, '$.target_entity', $target]
            ]);
        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with($fakeContext, '$.attribute', true);

        $this->activityManager->expects(self::once())
            ->method('addActivityTarget')
            ->with($email, $target)
            ->willReturn(true);

        $this->action->initialize($options);
        $this->action->execute($fakeContext);
    }

    public function testExecuteActionWithoutAttribute(): void
    {
        $options = [
            'email' => '$.email',
            'target_entity' => '$.target_entity'
        ];

        $fakeContext = ['fake', 'things', 'are', 'here'];

        $email = new Email();
        $target = new User();

        $this->contextAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [$fakeContext, '$.email', $email],
                [$fakeContext, '$.target_entity', $target]
            ]);
        $this->contextAccessor->expects(self::never())
            ->method('setValue');

        $this->activityManager->expects(self::once())
            ->method('addActivityTarget')
            ->with($email, $target)
            ->willReturn(true);

        $this->action->initialize($options);
        $this->action->execute($fakeContext);
    }
}
