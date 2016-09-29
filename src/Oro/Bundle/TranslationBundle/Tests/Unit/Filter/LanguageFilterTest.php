<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Filter\LanguageFilter;
use Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType;

class LanguageFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var LanguageFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->getMock(FormFactoryInterface::class);

        $this->filter = new LanguageFilter($this->formFactory, new FilterUtility());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        unset($this->formFactory, $this->filter);
    }

    public function testInit()
    {
        $this->filter->init('test', []);
        $this->assertAttributeEquals(
            [
                FilterUtility::FORM_OPTIONS_KEY => [
                    'field_options' => [
                        'class' => Language::class,
                        'property' => 'code',
                    ],
                ],
                FilterUtility::FRONTEND_TYPE_KEY => 'choice',
            ],
            'params',
            $this->filter
        );
    }

    public function testGetForm()
    {
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(LanguageFilterType::NAME);

        $this->filter->getForm();
    }
}
