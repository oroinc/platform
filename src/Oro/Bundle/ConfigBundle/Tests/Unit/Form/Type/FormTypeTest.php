<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class FormTypeTest extends TypeTestCase
{
    /** @var ConfigSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $subscriber;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var FormType */
    private $form;

    protected function setUp(): void
    {
        $this->subscriber = $this->getMockBuilder(ConfigSubscriber::class)
            ->onlyMethods(['__construct', 'preSubmit'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->createMock(ContainerInterface::class);

        $this->form = new FormType($this->subscriber, $this->container);

        parent::setUp();

        $this->dispatcher = new EventDispatcher();
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [$this->form],
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

    public function testAdditionalStaticConfiguratorWithUndefinedMethodName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected that "Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type\FormTypeTest::undefinedMethod" is a callable.'
        );

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

    public function testAdditionalServiceConfiguratorWithUndefinedMethodName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected that "@test_service::undefinedMethodName" is a callable.');

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

    public function testAdditionalServiceConfiguratorWithUnspecifiedMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected that "@test_service" is a callable.');

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

    public function testInvalidTypeOfAdditionalConfigurator()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "integer" given.');

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
