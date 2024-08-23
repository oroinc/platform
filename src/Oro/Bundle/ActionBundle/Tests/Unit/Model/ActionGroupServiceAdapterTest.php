<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\ActionGroupServiceAdapter;
use Oro\Bundle\ActionBundle\Model\Parameter;
use PHPUnit\Framework\TestCase;

class ActionGroupServiceAdapterTest extends TestCase
{
    private ParametersResolver $parametersResolver;

    protected function setUp(): void
    {
        $this->parametersResolver = new ParametersResolver();
    }

    public function testExecuteWithReturnValue(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';
        $returnValueName = 'formattedDate';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $method,
            $returnValueName,
            []
        );

        $data = new ActionData(['format' => 'd-m-Y']);
        $errors = new ArrayCollection();

        $resultData = $adapter->execute($data, $errors);

        $this->assertInstanceOf(ActionData::class, $resultData);
        $resultDataArray = $resultData->toArray();
        $this->assertArrayHasKey($returnValueName, $resultDataArray);
        $this->assertEquals('01-02-2000', $resultDataArray[$returnValueName]);
    }

    public function testExecuteWithoutReturnValue(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $method,
            null,
            []
        );

        $data = new ActionData(['format' => 'd-m-Y']);
        $errors = new ArrayCollection();

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
            $method,
            null,
            $parameterConfig
        );

        $data = new ActionData();
        $errors = new ArrayCollection();

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
            $method,
            null,
            $parameterConfig
        );

        $data = new ActionData(['date_format' => 'Y/m/d']);
        $errors = new ArrayCollection();

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
            $method,
            null,
            []
        );

        $definition = $adapter->getDefinition();

        $this->assertInstanceOf(ActionGroupDefinition::class, $definition);
        $this->assertSame(
            'service:' . get_class($service) . '::' . $method,
            $definition->getName()
        );
    }

    public function testIsAllowed(): void
    {
        $data = new ActionData();
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $method,
            null,
            []
        );

        $this->assertTrue($adapter->isAllowed($data));
    }

    public function testGetParameters(): void
    {
        $service = new \DateTime('2000-02-01', new \DateTimeZone('UTC'));
        $method = 'format';

        $adapter = new ActionGroupServiceAdapter(
            $this->parametersResolver,
            $service,
            $method,
            null,
            []
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
}
