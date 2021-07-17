<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Action;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\IntegrationBundle\Model\Action\PopulateIntegrationOwner;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class PopulateIntegrationOwnerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var DefaultOwnerHelper|MockObject */
    protected $defaultOwnerHelper;

    /** @var ActionInterface */
    protected $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();

        $this->defaultOwnerHelper = $this->getMockBuilder(DefaultOwnerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new class($this->contextAccessor, $this->defaultOwnerHelper) extends PopulateIntegrationOwner {
            public function xgetAttribute()
            {
                return $this->attribute;
            }

            public function xgetIntegration()
            {
                return $this->integration;
            }
        };

        /** @var EventDispatcher|MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeExceptions(array $options)
    {
        $this->expectException(InvalidParameterException::class);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return [
            [[]],
            [[1, 2]],
            [['attribute' => 'a']],
            [['integration' => 'a']],
        ];
    }

    public function testInitialize()
    {
        $attribute = 'a';
        $integration = 'b';

        $options = ['attribute' => $attribute, 'integration' => $integration];
        static::assertSame($this->action, $this->action->initialize($options));

        static::assertEquals($attribute, $this->action->xgetAttribute());
        static::assertEquals($integration, $this->action->xgetIntegration());
    }

    public function testExecuteIncorrectEntity()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Action "populate_channel_owner" expects an entity in parameter "attribute", string is given.'
        );

        $context = new \stdClass();
        $context->attr = 'test';
        $context->integration = $this->getMockBuilder(Channel::class)->disableOriginalConstructor()->getMock();

        $this->defaultOwnerHelper->expects(static::never())->method(static::anything());

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteIncorrectIntegration()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(\sprintf(
            'Action "populate_channel_owner" expects %s in parameter "integration", stdClass is given.',
            Channel::class
        ));

        $context = new \stdClass();
        $context->attr = new \stdClass();
        $context->integration = new \stdClass();

        $this->defaultOwnerHelper->expects(static::never())->method(static::anything());

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecute()
    {
        $context = new \stdClass();
        $context->attr = new \stdClass();
        $context->integration = $this->getMockBuilder(Channel::class)->disableOriginalConstructor()->getMock();

        $this->defaultOwnerHelper->expects(static::once())
            ->method('populateChannelOwner')
            ->with($context->attr, $context->integration);

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
