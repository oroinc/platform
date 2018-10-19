<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;
use Oro\Bundle\TranslationBundle\Form\Type\GedmoTranslationsType;
use Symfony\Component\Form\FormView;

class EmailTemplateTranslationTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailTemplateTranslationType */
    protected $type;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->type = new EmailTemplateTranslationType($this->configManager, GedmoTranslationsType::class);
    }

    protected function tearDown()
    {
        unset($this->type);
        unset($this->configManager);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $view = new FormView();
        $options = ['labels' => ['test' => 'test']];

        $this->type->buildView($view, $form, $options);
        $this->assertEquals($options['labels'], $view->vars['labels']);
    }

    public function testGetParent()
    {
        $this->assertEquals(GedmoTranslationsType::class, $this->type->getParent());
    }
}
