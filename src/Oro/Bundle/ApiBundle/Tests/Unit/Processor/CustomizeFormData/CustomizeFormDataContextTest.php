<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Symfony\Component\Form\Test\FormInterface;

class CustomizeFormDataContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomizeFormDataContext */
    private $context;

    protected function setUp()
    {
        $this->context = new CustomizeFormDataContext();
    }

    public function testIsInitialized()
    {
        self::assertFalse($this->context->isInitialized());

        $this->context->setForm($this->createMock(FormInterface::class));
        self::assertTrue($this->context->isInitialized());
    }

    public function testRootClassName()
    {
        self::assertNull($this->context->getRootClassName());

        $className = 'Test\Class';
        $this->context->setRootClassName($className);
        self::assertEquals($className, $this->context->getRootClassName());
    }

    public function testClassName()
    {
        self::assertNull($this->context->getClassName());

        $className = 'Test\Class';
        $this->context->setClassName($className);
        self::assertEquals($className, $this->context->getClassName());
    }

    public function testPropertyPath()
    {
        self::assertNull($this->context->getPropertyPath());

        $propertyPath = 'field1.field11';
        $this->context->setPropertyPath($propertyPath);
        self::assertEquals($propertyPath, $this->context->getPropertyPath());
    }

    public function testRootConfig()
    {
        self::assertNull($this->context->getRootConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setRootConfig($config);
        self::assertSame($config, $this->context->getRootConfig());

        $this->context->setRootConfig(null);
        self::assertNull($this->context->getRootConfig());
    }

    public function testConfig()
    {
        self::assertNull($this->context->getConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());

        $this->context->setConfig(null);
        self::assertNull($this->context->getConfig());
    }

    public function testIncludedEntities()
    {
        self::assertNull($this->context->getIncludedEntities());

        $includedEntities = $this->createMock(IncludedEntityCollection::class);
        $this->context->setIncludedEntities($includedEntities);
        self::assertSame($includedEntities, $this->context->getIncludedEntities());
    }

    public function testEvent()
    {
        self::assertNull($this->context->getPropertyPath());

        $eventName = 'test_event';
        $this->context->setEvent($eventName);
        self::assertEquals($eventName, $this->context->getEvent());
    }

    public function testParentAction()
    {
        self::assertNull($this->context->getPropertyPath());

        $actionName = 'test_action';
        $this->context->setParentAction($actionName);
        self::assertEquals($actionName, $this->context->getParentAction());

        $this->context->setParentAction(null);
        self::assertNull($this->context->getPropertyPath());
    }

    public function testForm()
    {
        $form = $this->createMock(FormInterface::class);
        $this->context->setForm($form);
        self::assertSame($form, $this->context->getForm());
    }

    public function testDataAndResult()
    {
        self::assertNull($this->context->getData());
        self::assertNull($this->context->getResult());
        self::assertTrue($this->context->hasResult());

        $data = ['key' => 'value'];
        $this->context->setData($data);
        self::assertSame($data, $this->context->getData());
        self::assertSame($data, $this->context->getResult());
        self::assertTrue($this->context->hasResult());

        $data = ['key1' => 'value1'];
        $this->context->setResult($data);
        self::assertSame($data, $this->context->getResult());
        self::assertSame($data, $this->context->getData());
        self::assertTrue($this->context->hasResult());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testRemoveResult()
    {
        $this->context->removeResult();
    }
}
