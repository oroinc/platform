<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTypeTestCase extends FormIntegrationTestCase
{
    protected FormFactoryInterface $factory;
    protected string $defaultTimezone = '';
    private string $oldTimezone;
    /** @var FormExtensionInterface[] */
    protected array $formExtensions = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        if ($this->defaultTimezone) {
            $this->oldTimezone = date_default_timezone_get();
            date_default_timezone_set($this->defaultTimezone);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->defaultTimezone) {
            date_default_timezone_set($this->oldTimezone);
        }
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return array_merge(
            $this->formExtensions,
            [new PreloadedExtension([], ['datetime' => [new DateTimeExtension()]])]
        );
    }

    protected function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->with($this->anything(), [])
            ->willReturnArgument(0);

        return $translator;
    }

    protected function createOptionsResolver(): OptionsResolver&MockObject
    {
        return $this->createMock(OptionsResolver::class);
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $defaultOptions, array $requiredOptions = []): void
    {
        $resolver = $this->createOptionsResolver();

        if ($defaultOptions) {
            $resolver->expects(self::once())
                ->method('setDefaults')
                ->with($defaultOptions)
                ->willReturnSelf();
        }

        if ($requiredOptions) {
            $resolver->expects(self::once())
                ->method('setRequired')
                ->with($requiredOptions)
                ->willReturnSelf();
        }

        $this->getTestFormType()->configureOptions($resolver);
    }

    abstract public function configureOptionsDataProvider(): array;

    /**
     * @dataProvider bindDataProvider
     */
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = []
    ): void {
        $form = $this->factory->create(get_class($this->getTestFormType()), null, $customOptions);

        $form->submit($bindData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($formData, $form->getData());

        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            self::assertArrayHasKey($key, $view->vars);
            self::assertEquals($value, $view->vars[$key]);
        }
    }

    abstract public function bindDataProvider(): array;

    abstract protected function getTestFormType(): AbstractType;
}
