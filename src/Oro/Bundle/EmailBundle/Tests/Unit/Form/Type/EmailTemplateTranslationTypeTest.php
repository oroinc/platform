<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;

class EmailTemplateTranslationTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailTemplateTranslationType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->type = new EmailTemplateTranslationType($this->configManager);
    }

    protected function tearDown()
    {
        unset($this->type);
        unset($this->configManager);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->setDefaultOptions($resolver);
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

    public function testGetName()
    {
        $this->assertEquals('oro_email_emailtemplate_translatation', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('a2lix_translations_gedmo', $this->type->getParent());
    }
}
