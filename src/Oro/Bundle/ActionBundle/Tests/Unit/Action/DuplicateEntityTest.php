<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Action\DuplicateEntity;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Duplicator\DuplicatorFactory;
use Oro\Component\Duplicator\Filter\Filter;
use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Matcher\Matcher;
use Oro\Component\Duplicator\Matcher\MatcherFactory;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class DuplicateEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var DuplicateEntity */
    private $action;

    protected function setUp(): void
    {
        $this->action = new DuplicateEntity(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
        $this->action->setDuplicatorFactory($this->getDuplicateFactory());
    }

    public function testInitialize()
    {
        $options = [
            DuplicateEntity::OPTION_KEY_TARGET => 'test_value',
            DuplicateEntity::OPTION_KEY_SETTINGS => [],
            DuplicateEntity::OPTION_KEY_ATTRIBUTE => ['copyResult'],
        ];

        self::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exception, string $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            [
                'options' => [DuplicateEntity::OPTION_KEY_TARGET => ['target']],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => "Option 'target' should be string or PropertyPath",
            ],
            [
                'options' => [DuplicateEntity::OPTION_KEY_SETTINGS => 'wrong settings'],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => "Option 'settings' should be array",
            ],
            [
                'options' => [],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Attribute name parameter is required',
            ],
        ];
    }

    public function testExecute()
    {
        $child = new \stdClass();
        $child->name = 'child';
        $target = new \stdClass();
        $target->name = 'parent';
        $target->child = $child;

        $context = new ActionData(['data' => $target]);
        $this->action->initialize([DuplicateEntity::OPTION_KEY_ATTRIBUTE => 'copyResult']);
        $this->action->execute($context);
        /** @var \stdClass $copyObject */
        $copyObject = $context['copyResult'];
        self::assertNotSame($copyObject, $target);
        self::assertEquals($copyObject, $target);
        self::assertSame($copyObject->child, $copyObject->child);
    }

    public function testExecuteWithEntity()
    {
        $target = new \stdClass();

        $contextAccessor = $this->createMock(ContextAccessor::class);

        $action = new DuplicateEntity($contextAccessor);
        $action->setDispatcher($this->createMock(EventDispatcherInterface::class));
        $action->setDuplicatorFactory($this->getDuplicateFactory());

        $context = $this->createMock(ActionData::class);

        $options = [
            DuplicateEntity::OPTION_KEY_ENTITY => '$.data',
            DuplicateEntity::OPTION_KEY_ATTRIBUTE => 'copyResult',
            DuplicateEntity::OPTION_KEY_SETTINGS => [
                [
                    ['replaceValue', new PropertyPath('$.dataForField1')],
                    ['propertyName', ['field1']]
                ],
            ],
        ];
        $contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [$context, $options[DuplicateEntity::OPTION_KEY_ENTITY], $target],
                [$context, '$.dataForField1', 'newValue'],
            ]);

        $action->initialize($options);
        $action->execute($context);
    }

    private function getDuplicateFactory(): DuplicatorFactory
    {
        $filterFactory = $this->createMock(FilterFactory::class);
        $filterFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->createMock(Filter::class));

        $matcherFactory = $this->createMock(MatcherFactory::class);
        $matcherFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->createMock(Matcher::class));

        return new DuplicatorFactory($matcherFactory, $filterFactory);
    }
}
