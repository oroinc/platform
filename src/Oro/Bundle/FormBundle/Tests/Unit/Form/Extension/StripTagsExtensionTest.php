<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Extension;

use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StripTagsExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var  StripTagsExtension */
    protected $formExtension;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $htmlTagHelper;

    protected function setUp()
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->formExtension = new StripTagsExtension($this->htmlTagHelper);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with(StripTagsExtension::OPTION_NAME);
        $this->formExtension->configureOptions($resolver);
    }

    /**
     * @param bool $stripTags
     *
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm($stripTags)
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly((int)$stripTags))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, function () {
            });
        $this->formExtension->buildForm($builder, [StripTagsExtension::OPTION_NAME => $stripTags]);
    }

    /**
     * @return array
     */
    public function buildFormDataProvider()
    {
        return [
            'positive' => ['stripTags' => true],
            'negative' => ['stripTags' => false],
            'negative null' => ['stripTags' => null],
        ];
    }
}
