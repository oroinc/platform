<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LocalizationSelectType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->type = new LocalizationSelectType();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals('oro_localization', $options['autocomplete_alias']);
                    $this->assertEquals('oro_locale_localization_create', $options['create_form_route']);
                    $this->assertEquals(
                        ['placeholder' => 'oro.locale.localization.form.placeholder.choose'],
                        $options['configs']
                    );
                }
            );

        $this->type->configureOptions($resolver);
    }
}
