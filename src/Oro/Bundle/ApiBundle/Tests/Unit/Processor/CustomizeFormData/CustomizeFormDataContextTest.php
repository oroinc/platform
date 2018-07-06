<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

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

    public function testRootClassName()
    {
        self::assertNull($this->context->getRootClassName());

        $this->context->setRootClassName('Test\Class');
        self::assertEquals('Test\Class', $this->context->getRootClassName());
    }

    public function testClassName()
    {
        self::assertNull($this->context->getClassName());

        $this->context->setClassName('Test\Class');
        self::assertEquals('Test\Class', $this->context->getClassName());
    }

    public function testPropertyPath()
    {
        self::assertNull($this->context->getPropertyPath());

        $this->context->setPropertyPath('field1.field11');
        self::assertEquals('field1.field11', $this->context->getPropertyPath());
    }

    public function testRootConfig()
    {
        self::assertNull($this->context->getRootConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertNull($this->context->getRootConfig());

        $this->context->setPropertyPath('test');
        self::assertSame($config, $this->context->getRootConfig());
    }

    public function testConfigForKnownField()
    {
        self::assertNull($this->context->getConfig());

        $config = new EntityDefinitionConfig();
        $config
            ->addField('field1')
            ->createAndSetTargetEntity()
            ->addField('field11')
            ->createAndSetTargetEntity();

        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());

        $this->context->setPropertyPath('field1.field11');
        self::assertSame(
            $config->getField('field1')->getTargetEntity()->getField('field11')->getTargetEntity(),
            $this->context->getConfig()
        );
    }

    public function testConfigForUnknownField()
    {
        self::assertNull($this->context->getConfig());

        $config = new EntityDefinitionConfig();
        $config->addField('field1');

        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());

        $this->context->setPropertyPath('unknownField.field11');
        self::assertNull($this->context->getConfig());
    }

    public function testConfigForExcludedField()
    {
        self::assertNull($this->context->getConfig());

        $config = new EntityDefinitionConfig();
        $config
            ->addField('field1')
            ->createAndSetTargetEntity()
            ->addField('field11')
            ->createAndSetTargetEntity();
        $config->getField('field1')->setExcluded();

        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());

        $this->context->setPropertyPath('field1.field11');
        self::assertSame(
            $config->getField('field1')->getTargetEntity()->getField('field11')->getTargetEntity(),
            $this->context->getConfig()
        );
    }

    public function testForm()
    {
        $form = $this->createMock(FormInterface::class);
        $this->context->setForm($form);
        self::assertSame($form, $this->context->getForm());
    }
}
