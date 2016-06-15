<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;

use Oro\Bundle\LocaleBundle\Form\Type\FormattingSelectType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class FormattingSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LocalizationChoicesProvider
     */
    protected $provider;

    /**
     * @var AbstractType
     */
    protected $formType;

    public function setUp()
    {
        parent::setUp();

        $this->provider = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new FormattingSelectType($this->provider);
    }

    public function tearDown()
    {
        unset($this->provider, $this->formType);

        parent::tearDown();
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(FormattingSelectType::NAME, $this->formType->getName());
    }

    public function testBuildForm()
    {
        $data =  ['en' => 'English', 'es' => 'Spain'];

        $this->provider->expects($this->once())->method('getFormattingChoices')->willReturn($data);

        $form = $this->factory->create($this->formType);

        $choices = $form->getConfig()->getOption('choices');

        $this->assertEquals($data, $choices);
    }
}
