<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Action;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\IntegrationBundle\Model\Action\PopulateIntegrationOwner;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class PopulateIntegrationOwnerTest extends TestCase
{
    private DefaultOwnerHelper&MockObject $defaultOwnerHelper;
    private ActionInterface $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultOwnerHelper = $this->createMock(DefaultOwnerHelper::class);

        $this->action = new PopulateIntegrationOwner(new ContextAccessor(), $this->defaultOwnerHelper);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeExceptions(array $options): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->action->initialize($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            [[]],
            [[1, 2]],
            [['attribute' => 'a']],
            [['integration' => 'a']],
        ];
    }

    public function testInitialize(): void
    {
        $attribute = 'a';
        $integration = 'b';

        $options = ['attribute' => $attribute, 'integration' => $integration];
        self::assertSame($this->action, $this->action->initialize($options));

        self::assertEquals($attribute, ReflectionUtil::getPropertyValue($this->action, 'attribute'));
        self::assertEquals($integration, ReflectionUtil::getPropertyValue($this->action, 'integration'));
    }

    public function testExecuteIncorrectEntity(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Action "populate_channel_owner" expects an entity in parameter "attribute", string is given.'
        );

        $context = new \stdClass();
        $context->attr = 'test';
        $context->integration = $this->createMock(Channel::class);

        $this->defaultOwnerHelper->expects(self::never())
            ->method(self::anything());

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteIncorrectIntegration(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(sprintf(
            'Action "populate_channel_owner" expects %s in parameter "integration", stdClass is given.',
            Channel::class
        ));

        $context = new \stdClass();
        $context->attr = new \stdClass();
        $context->integration = new \stdClass();

        $this->defaultOwnerHelper->expects(self::never())
            ->method(self::anything());

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecute(): void
    {
        $context = new \stdClass();
        $context->attr = new \stdClass();
        $context->integration = $this->createMock(Channel::class);

        $this->defaultOwnerHelper->expects(self::once())
            ->method('populateChannelOwner')
            ->with($context->attr, $context->integration);

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
