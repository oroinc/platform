<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractTypeTestCase extends FormIntegrationTestCase
{
    /**
     * @var \Symfony\Component\Form\FormFactory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $defaultTimezone = null;

    /**
     * @var string
     */
    private $oldTimezone;

    /**
     * @var FormExtensionInterface[]
     */
    protected $formExtensions = array();

    protected function setUp()
    {
        parent::setUp();
        if ($this->defaultTimezone) {
            $this->oldTimezone = date_default_timezone_get();
            date_default_timezone_set($this->defaultTimezone);
        }
    }

    protected function tearDown()
    {
        parent::tearDown();
        if ($this->defaultTimezone) {
            date_default_timezone_set($this->oldTimezone);
        }
    }

    /**
     * @return TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockTranslator()
    {
        $translator = $this->getMockForAbstractClass('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->with($this->anything(), array())
            ->will($this->returnArgument(0));

        return $translator;
    }

    /**
     * @return OptionsResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockOptionsResolver()
    {
        return $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider configureOptionsDataProvider
     * @param array $defaultOptions
     * @param array $requiredOptions
     */
    public function testConfigureOptions(array $defaultOptions, array $requiredOptions = [])
    {
        $resolver = $this->createMockOptionsResolver();

        if ($defaultOptions) {
            $resolver->expects($this->once())->method('setDefaults')->with($defaultOptions)->will($this->returnSelf());
        }

        if ($requiredOptions) {
            $resolver->expects($this->once())->method('setRequired')->with($requiredOptions)->will($this->returnSelf());
        }

        $this->getTestFormType()->configureOptions($resolver);
    }

    /**
     * Data provider for testBindData
     *
     * @return array
     */
    abstract public function configureOptionsDataProvider();

    /**
     * @dataProvider bindDataProvider
     * @param array $bindData
     * @param array $formData
     * @param array $viewData
     * @param array $customOptions
     */
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = array()
    ) {
        $form = $this->factory->create(get_class($this->getTestFormType()), null, $customOptions);

        $form->submit($bindData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());

        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value, $view->vars[$key]);
        }
    }

    /**
     * Data provider for testBindData
     *
     * @return array
     */
    abstract public function bindDataProvider();

    /**
     * @return AbstractType
     */
    abstract protected function getTestFormType();

    /**
     * @return array|FormExtensionInterface[]
     */
    protected function getExtensions()
    {
        return array_merge(
            $this->formExtensions,
            [new PreloadedExtension([], ['datetime' => [new DateTimeExtension()]])]
        );
    }

    /**
     * @param string  $class
     * @param array $events
     *
     * @return mixed
     */
    public function getMockSubscriber($class, array $events = [])
    {
        $mock = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventListener = new MutableFormEventSubscriber($mock);
        $eventListener->setSubscribedEvents($events);

        return $eventListener;
    }
}
