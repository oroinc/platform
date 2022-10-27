<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StripTagsExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /** @var StripTagsExtension */
    private $formExtension;

    protected function setUp(): void
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $container = TestContainerBuilder::create()
            ->add('oro_ui.html_tag_helper', $this->htmlTagHelper)
            ->getContainer($this);

        $this->formExtension = new StripTagsExtension($container);
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

    public function buildFormDataProvider(): array
    {
        return [
            'positive' => ['stripTags' => true],
            'negative' => ['stripTags' => false],
            'negative null' => ['stripTags' => null],
        ];
    }
}
