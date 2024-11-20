<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Event\ActionGroupEventDispatcher;
use Oro\Bundle\ActionBundle\Event\ActionGroupExecuteEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupGuardEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupPreExecuteEvent;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\ActionGroupServiceAdapter;
use Oro\Bundle\ActionBundle\Model\Parameter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionGroupServiceAdapterTest extends TestCase
{
    private ParametersResolver $parametersResolver;
    private ActionGroupEventDispatcher|MockObject $eventDispatcher;
    private ActionGroupDefinition $definition;

    #[\Override]
    protected function setUp(): void
    {
        $this->parametersResolver = new ParametersResolver();
        $this->eventDispatcher = $this->createMock(ActionGroupEventDispatcher::class);
        $this->definition = new ActionGroupDefinition();
        $this->definition->setName('test_action_group');
    }

    public function testExecuteWithReturnValue(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';
        $returnValueName = 'formattedDate';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            $returnValueName,
            [],
            $this->definition
        );

        $data = new ActionData(['format' => 'd-m-Y']);
        $errors = new ArrayCollection();
        $this->assertEventDispatchForExecute($data, $errors);

        $resultData = $adapter->execute($data, $errors);

        $this->assertInstanceOf(ActionData::class, $resultData);
        $resultDataArray = $resultData->toArray();
        $this->assertArrayHasKey($returnValueName, $resultDataArray);
        $this->assertEquals('01-02-2000', $resultDataArray[$returnValueName]);
    }

    public function testExecuteNotAllowed(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            null,
            [],
            $this->definition
        );

        $data = new ActionData(['format' => 'd-m-Y']);
        $errors = new ArrayCollection();

        $event = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(function (ActionGroupGuardEvent $event) {
                $event->setAllowed(false);
            });

        $this->expectException(ForbiddenActionGroupException::class);
        $this->expectExceptionMessage(sprintf('ActionGroup "%s" is not allowed', $this->definition->getName()));

        $adapter->execute($data, $errors);
    }

    public function testExecuteWithoutReturnValue(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            null,
            [],
            $this->definition
        );

        $data = new ActionData(['format' => 'd-m-Y']);
        $errors = new ArrayCollection();
        $this->assertEventDispatchForExecute($data, $errors);

        $resultData = $adapter->execute($data, $errors);

        $this->assertInstanceOf(ActionData::class, $resultData);
        $resultDataArray = $resultData->toArray();
        $this->assertArrayHasKey(ActionGroupServiceAdapter::RESULT_VALUE_KEY, $resultDataArray);
        $this->assertEquals('01-02-2000', $resultDataArray[ActionGroupServiceAdapter::RESULT_VALUE_KEY]);
    }

    public function testExecuteWithParametersMappingAndDefaultValue(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';
        $parameterConfig = ['date_format' => ['default' => 'm/d/Y', 'service_argument_name' => 'format']];

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            null,
            $parameterConfig,
            $this->definition
        );

        $data = new ActionData();
        $errors = new ArrayCollection();
        $this->assertEventDispatchForExecute($data, $errors);

        $resultData = $adapter->execute($data, $errors);

        $this->assertInstanceOf(ActionData::class, $resultData);
        $resultDataArray = $resultData->toArray();
        $this->assertArrayHasKey(ActionGroupServiceAdapter::RESULT_VALUE_KEY, $resultDataArray);
        $this->assertEquals('02/01/2000', $resultDataArray[ActionGroupServiceAdapter::RESULT_VALUE_KEY]);
    }

    public function testExecuteWithParametersMapping(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';
        $parameterConfig = ['date_format' => ['default' => 'm/d/Y', 'service_argument_name' => 'format']];

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            null,
            $parameterConfig,
            $this->definition
        );

        $data = new ActionData(['date_format' => 'Y/m/d']);
        $errors = new ArrayCollection();
        $this->assertEventDispatchForExecute($data, $errors);

        $resultData = $adapter->execute($data, $errors);

        $this->assertInstanceOf(ActionData::class, $resultData);
        $resultDataArray = $resultData->toArray();
        $this->assertArrayHasKey(ActionGroupServiceAdapter::RESULT_VALUE_KEY, $resultDataArray);
        $this->assertEquals('2000/02/01', $resultDataArray[ActionGroupServiceAdapter::RESULT_VALUE_KEY]);
    }

    public function testGetDefinition(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            null,
            [],
            $this->definition
        );

        $definition = $adapter->getDefinition();

        $this->assertInstanceOf(ActionGroupDefinition::class, $definition);
        $this->assertSame(
            'test_action_group',
            $definition->getName()
        );
    }

    public function testIsAllowed(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $event = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event);

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            null,
            [],
            $this->definition
        );

        $this->assertTrue($adapter->isAllowed($data, $errors));
    }

    public function testIsAllowedDisallowedByEvent(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $event = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(function (ActionGroupGuardEvent $event) {
                $event->setAllowed(false);
            });

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            null,
            [],
            $this->definition
        );

        $this->assertFalse($adapter->isAllowed($data, $errors));
    }

    public function testGetParameters(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $this->eventDispatcher,
            $method,
            null,
            [],
            $this->definition
        );

        $parameters = $adapter->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertArrayHasKey('format', $parameters);
        $formatParameter = $parameters['format'];

        $this->assertInstanceOf(Parameter::class, $formatParameter);
        $this->assertEquals('string', $formatParameter->getType());
        $this->assertFalse($formatParameter->isNullsAllowed());
        $this->assertTrue($formatParameter->isRequired());
    }

    private function assertEventDispatchForExecute(ActionData $data, ArrayCollection $errors): void
    {
        $preExecuteEvent = new ActionGroupPreExecuteEvent($data, $this->definition, $errors);
        $executeEvent = new ActionGroupExecuteEvent($data, $this->definition, $errors);
        $guardEvent = new ActionGroupGuardEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$guardEvent],
                [$preExecuteEvent],
                [$executeEvent],
            );
    }
}
