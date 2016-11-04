<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\ApiBundle\Form\EventListener\CreateListener;
use Oro\Bundle\ApiBundle\Form\Type\BooleanType;
use Oro\Bundle\ApiBundle\Tests\Unit\Form\EventListener\Fixtures as Entity;

class CreateListenerTest extends TypeTestCase
{
    public function testWithoutDefaultValues()
    {
        $data = new Entity\User();

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['data_class' => Entity\User::class]
        );
        $formBuilder->addEventSubscriber(new CreateListener());
        $formBuilder->add('name');
        $formBuilder->add('enabled', new BooleanType());

        $form = $formBuilder->getForm();
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        self::assertNull($data->name);
        self::assertNull($data->enabled);
    }

    public function testWithDefaultValues()
    {
        $data = new Entity\User();
        $data->name = 'defaultName';
        $data->enabled = false;

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['data_class' => Entity\User::class]
        );
        $formBuilder->addEventSubscriber(new CreateListener());
        $formBuilder->add('name');
        $formBuilder->add('enabled', new BooleanType());

        $form = $formBuilder->getForm();
        $form->submit([]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        self::assertSame('defaultName', $data->name);
        self::assertFalse($data->enabled);
    }

    public function testShouldOverrideDefaultValues()
    {
        $data = new Entity\User();
        $data->name = 'defaultName';
        $data->enabled = false;

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['data_class' => Entity\User::class]
        );
        $formBuilder->addEventSubscriber(new CreateListener());
        $formBuilder->add('name');
        $formBuilder->add('enabled', new BooleanType());

        $form = $formBuilder->getForm();
        $form->submit(['name' => 'test', 'enabled' => true]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        self::assertSame('test', $data->name);
        self::assertTrue($data->enabled);
    }

    public function testShouldOverrideDefaultValuesWithNull()
    {
        $data = new Entity\User();
        $data->name = 'defaultName';
        $data->enabled = false;

        $formBuilder = $this->factory->createBuilder(
            FormType::class,
            $data,
            ['data_class' => Entity\User::class]
        );
        $formBuilder->addEventSubscriber(new CreateListener());
        $formBuilder->add('name');
        $formBuilder->add('enabled', new BooleanType());

        $form = $formBuilder->getForm();
        $form->submit(['name' => null, 'enabled' => null]);
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        self::assertNull($data->name);
        self::assertNull($data->enabled);
    }
}
