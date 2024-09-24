<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroHiddenNumberType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroHiddenNumberTypeTest extends FormIntegrationTestCase
{
    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var OroHiddenNumberType */
    private $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->formType = new OroHiddenNumberType($this->numberFormatter);

        parent::setUp();

        // we test against "de_DE", so we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);
    }

    public function testGetParent()
    {
        self::assertEquals(NumberType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals('oro_hidden_number', $this->formType->getBlockPrefix());
    }

    public function testDeFormatting()
    {
        $this->numberFormatter->expects(self::once())
            ->method('getAttribute')
            ->with(\NumberFormatter::GROUPING_USED)
            ->willReturn(true);

        $defaultLocale = \Locale::getDefault();
        \Locale::setDefault('de_DE');
        try {
            $form = $this->factory->create(OroHiddenNumberType::class);
            $form->setData('12345.67890');
            $view = $form->createView();
        } finally {
            \Locale::setDefault($defaultLocale);
        }

        self::assertSame('12.345,679', $view->vars['value']);
    }

    public function testEnFormatting()
    {
        $this->numberFormatter->expects(self::once())
            ->method('getAttribute')
            ->with(\NumberFormatter::GROUPING_USED)
            ->willReturn(false);

        $defaultLocale = \Locale::getDefault();
        \Locale::setDefault('en_US');
        try {
            $form = $this->factory->create(OroHiddenNumberType::class);
            $form->setData('12345.67890');
            $view = $form->createView();
        } finally {
            \Locale::setDefault($defaultLocale);
        }

        self::assertSame('12345.679', $view->vars['value']);
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(bool $groupingUsed, array $expectedOptions)
    {
        $this->numberFormatter->expects(self::once())
            ->method('getAttribute')
            ->with(\NumberFormatter::GROUPING_USED)
            ->willReturn($groupingUsed);

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve([]);

        self::assertEquals($expectedOptions, $options);
    }

    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'groupingUsed' => true,
                'expectedOptions' => [
                    'grouping' => true,
                ],
            ],
            [
                'groupingUsed' => false,
                'expectedOptions' => [
                    'grouping' => false,
                ],
            ],
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }
}
