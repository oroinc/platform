<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var FilterType
     */
    protected $type;

    protected function setUp()
    {
        $translator = $this->createMockTranslator();
        $this->type = new FilterType($translator);
        $this->formExtensions[] = new PreloadedExtension([$this->type], []);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider()
    {
        return array(
            array(
                'defaultOptions' => array(
                    'field_type' => TextType::class,
                    'field_options' => array(),
                    'operator_choices' => array(),
                    'operator_type' => ChoiceType::class,
                    'operator_options' => array(),
                    'show_filter' => false,
                    'lazy' => false
                ),
                'requiredOptions' => array(
                    'field_type',
                    'field_options',
                    'operator_choices',
                    'operator_type',
                    'operator_options',
                    'show_filter'
                )
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return array(
            'empty data' => array(
                'bindData' => array(),
                'formData' => array('type' => null, 'value' => null),
                'viewData' => array(
                    'value' => array('type' => '', 'value' => ''),
                ),
                'customOptions' => array(
                    'operator_choices' => array()
                ),
            ),
            'empty choice' => array(
                'bindData' => array('type' => '1', 'value' => ''),
                'formData' => array('value' => null),
                'viewData' => array(
                    'value' => array('type' => '1', 'value' => ''),
                ),
                'customOptions' => array(
                    'operator_choices' => array()
                ),
            ),
            'invalid choice' => array(
                'bindData' => array('type' => '-1', 'value' => ''),
                'formData' => array('value' => null),
                'viewData' => array(
                    'value' => array('type' => '-1', 'value' => ''),
                ),
                'customOptions' => array(
                    'operator_choices' => array(
                        'Choice 1' => 1,
                    )
                ),
            ),
            'without choice' => array(
                'bindData' => array('value' => 'text'),
                'formData' => array('type' => null, 'value' => 'text'),
                'viewData' => array(
                    'value' => array('type' => '', 'value' => 'text'),
                ),
                'customOptions' => array(
                    'operator_choices' => array(
                        'Choice 1' => 1
                    )
                ),
            ),
        );
    }
}
