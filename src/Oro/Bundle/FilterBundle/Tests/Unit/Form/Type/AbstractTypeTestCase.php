<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTypeTestCase extends FormIntegrationTestCase
{
    /** @var \Symfony\Component\Form\FormFactory */
    protected $factory;

    /** @var string */
    protected $defaultTimezone;

    /** @var string */
    private $oldTimezone;

    /** @var FormExtensionInterface[] */
    protected $formExtensions = [];

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->defaultTimezone) {
            $this->oldTimezone = date_default_timezone_get();
            date_default_timezone_set($this->defaultTimezone);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->defaultTimezone) {
            date_default_timezone_set($this->oldTimezone);
        }
    }

    protected function createMockTranslator(): TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->with($this->anything(), [])
            ->willReturnArgument(0);

        return $translator;
    }

    protected function createMockOptionsResolver(): OptionsResolver|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(OptionsResolver::class);
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $defaultOptions, array $requiredOptions = [])
    {
        $resolver = $this->createMockOptionsResolver();

        if ($defaultOptions) {
            $resolver->expects($this->once())
                ->method('setDefaults')
                ->with($defaultOptions)
                ->willReturnSelf();
        }

        if ($requiredOptions) {
            $resolver->expects($this->once())
                ->method('setRequired')
                ->with($requiredOptions)
                ->willReturnSelf();
        }

        $this->getTestFormType()->configureOptions($resolver);
    }

    /**
     * Data provider for testBindData
     */
    abstract public function configureOptionsDataProvider(): array;

    /**
     * @dataProvider bindDataProvider
     */
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = []
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
     */
    abstract public function bindDataProvider(): array;

    abstract protected function getTestFormType(): AbstractType;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(
            $this->formExtensions,
            [new PreloadedExtension([], ['datetime' => [new DateTimeExtension()]])]
        );
    }

    protected function getSubscriber(string $class, array $events = []): MutableFormEventSubscriber
    {
        $subscriber = new MutableFormEventSubscriber($this->createMock($class));
        $subscriber->setSubscribedEvents($events);

        return $subscriber;
    }
}
