<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class FormTypeTest extends TypeTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $subscriber;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var FormType */
    protected $form;

    protected function setUp()
    {
        $this->subscriber = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber')
            ->setMethods(['__construct', 'preSubmit'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->form = new FormType($this->subscriber, $this->container);

        parent::setUp();

        $this->dispatcher = new EventDispatcher();
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    $this->form
                ],
                [SymfonyFormType::class => [new DataBlockExtension()]]
            )
        ];
    }

    public function testBuildForm()
    {
        $this->subscriber->expects(self::once())
            ->method('preSubmit');

        $form = $this->factory->create(FormType::class, null, ['block_config' => []]);
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
    }

    public function testAdditionalStaticConfigurator()
    {
        $form = $this->factory->create(
            FormType::class,
            null,
            [
                'block_config' => [
                    ['configurator' => __CLASS__ . '::staticConfigurator']
                ]
            ]
        );
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->getConfig()->getAttribute('isConfiguratorApplied'));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected that "Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type\FormTypeTest::undefinedMethod" is a callable.
     */
    // @codingStandardsIgnoreEnd
    public function testAdditionalStaticConfiguratorWithUndefinedMethodName()
    {
        $form = $this->factory->create(
            FormType::class,
            null,
            [
                'block_config' => [
                    ['configurator' => __CLASS__ . '::undefinedMethod']
                ]
            ]
        );
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->getConfig()->hasAttribute('isConfiguratorApplied'));
    }

    public function testAdditionalServiceConfigurator()
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('test_service')
            ->willReturn($this);

        $form = $this->factory->create(
            FormType::class,
            null,
            [
                'block_config' => [
                    ['configurator' => '@test_service::serviceConfigurator']
                ]
            ]
        );
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->getConfig()->getAttribute('isConfiguratorApplied'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected that "@test_service::undefinedMethodName" is a callable.
     */
    public function testAdditionalServiceConfiguratorWithUndefinedMethodName()
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with('test_service')
            ->willReturn($this);

        $form = $this->factory->create(
            FormType::class,
            null,
            [
                'block_config' => [
                    ['configurator' => '@test_service::undefinedMethodName']
                ]
            ]
        );
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->getConfig()->hasAttribute('isConfiguratorApplied'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected that "@test_service" is a callable.
     */
    public function testAdditionalServiceConfiguratorWithUnspecifiedMethod()
    {
        $this->container->expects(self::never())
            ->method('get');

        $form = $this->factory->create(
            FormType::class,
            null,
            [
                'block_config' => [
                    ['configurator' => '@test_service']
                ]
            ]
        );
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->getConfig()->hasAttribute('isConfiguratorApplied'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testInvalidTypeOfAdditionalConfigurator()
    {
        $form = $this->factory->create(
            FormType::class,
            null,
            [
                'block_config' => [
                    ['configurator' => 123]
                ]
            ]
        );
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->getConfig()->getAttribute('isConfiguratorApplied'));
    }

    public static function staticConfigurator(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('isConfiguratorApplied', true);
    }

    public function serviceConfigurator(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('isConfiguratorApplied', true);
    }
}
