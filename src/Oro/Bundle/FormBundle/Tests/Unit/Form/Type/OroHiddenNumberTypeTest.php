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
    /**
     * @var OroHiddenNumberType
     */
    private $formType;

    /**
     * @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $numberFormatter;

    protected function setUp()
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
        \Locale::setDefault('de_DE');

        $this->numberFormatter
            ->expects(self::once())
            ->method('getAttribute')
            ->with(\NumberFormatter::GROUPING_USED)
            ->willReturn(true);

        $form = $this->factory->create(OroHiddenNumberType::class);
        $form->setData('12345.67890');

        self::assertSame('12.345,679', $form->createView()->vars['value']);
    }

    public function testEnFormatting()
    {
        \Locale::setDefault('en_US');

        $this->numberFormatter
            ->expects(self::once())
            ->method('getAttribute')
            ->with(\NumberFormatter::GROUPING_USED)
            ->willReturn(false);

        $form = $this->factory->create(OroHiddenNumberType::class);
        $form->setData('12345.67890');

        self::assertSame('12345.679', $form->createView()->vars['value']);
    }

    /**
     * @dataProvider configureOptionsDataProvider
     *
     * @param bool $groupingUsed
     * @param array $expectedOptions
     */
    public function testConfigureOptions(bool $groupingUsed, array $expectedOptions)
    {
        $this->numberFormatter
            ->expects(self::once())
            ->method('getAttribute')
            ->with(\NumberFormatter::GROUPING_USED)
            ->willReturn($groupingUsed);

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve([]);

        self::assertEquals($expectedOptions, $options);
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OroHiddenNumberType::class => $this->formType
                ],
                []
            ),
        ];
    }
}
