<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FormTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([]);
    }

    protected function tearDown()
    {
        $this->switchToDefaultFormExtension();
        parent::tearDown();
    }

    public function testDefaultForm()
    {
        $form = $this->getForm(['csrf_protection' => false]);
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => 'test']);
        $this->assertTrue($form->isSubmitted(), 'isSubmitted');
        $this->assertTrue($form->isValid(), 'isValid');

        $this->assertSame(123, $object->getId());
        $this->assertSame('test', $object->getTitle());
    }

    public function testApiForm()
    {
        $this->switchToApiFormExtension();

        $form = $this->getForm();
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => 'test']);
        $this->assertTrue($form->isSubmitted(), 'isSubmitted');
        $this->assertTrue($form->isValid(), 'isValid');

        $this->assertSame(123, $object->getId());
        $this->assertSame('test', $object->getTitle());
    }

    public function testDefaultFormValidation()
    {
        $form = $this->getForm(['csrf_protection' => false]);
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => '']);
        $this->assertTrue($form->isSubmitted(), 'isSubmitted');
        $this->assertFalse($form->isValid(), 'isValid');
    }

    public function testApiFormValidation()
    {
        $this->switchToApiFormExtension();

        $form = $this->getForm();
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => '']);
        $this->assertTrue($form->isSubmitted(), 'isSubmitted');
        $this->assertFalse($form->isValid(), 'isValid');
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testApiFormShouldNotHaveCsrfExtension()
    {
        $this->switchToApiFormExtension();

        $this->getForm(['csrf_protection' => false]);
    }

    protected function switchToDefaultFormExtension()
    {
        /** @var FormExtensionSwitcherInterface $formExtensionSwitcher */
        $formExtensionSwitcher = $this->getContainer()->get('form.registry');
        $formExtensionSwitcher->switchToDefaultFormExtension();
    }

    protected function switchToApiFormExtension()
    {
        /** @var FormExtensionSwitcherInterface $formExtensionSwitcher */
        $formExtensionSwitcher = $this->getContainer()->get('form.registry');
        $formExtensionSwitcher->switchToApiFormExtension();
    }

    /**
     * @param array $options
     *
     * @return FormInterface
     */
    protected function getForm(array $options = [])
    {
        $options['data_class'] = 'Oro\Bundle\ApiBundle\Tests\Functional\TestObject';
        $options['extra_fields_message'] = 'This form should not contain extra fields: "{{ extra_fields }}"';
        $form = $this->getContainer()->get('form.factory')->create('form', null, $options);
        $form->add('id', 'integer');
        $form->add('title', 'text');

        return $form;
    }
}
