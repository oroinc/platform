<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TextFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var TextFilterType
     */
    private $type;

    protected function setUp()
    {
        $translator             = $this->createMockTranslator();
        $this->type = new TextFilterType($translator);
        $this->formExtensions[] = new CustomFormExtension(array(new FilterType($translator)));
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
        return [
            [
                'defaultOptions' => [
                    'field_type' => TextType::class,
                    'operator_choices' => [
                        'oro.filter.form.label_type_contains' => TextFilterType::TYPE_CONTAINS,
                        'oro.filter.form.label_type_not_contains' => TextFilterType::TYPE_NOT_CONTAINS,
                        'oro.filter.form.label_type_equals' => TextFilterType::TYPE_EQUAL,
                        'oro.filter.form.label_type_start_with' => TextFilterType::TYPE_STARTS_WITH,
                        'oro.filter.form.label_type_end_with' => TextFilterType::TYPE_ENDS_WITH,
                        'oro.filter.form.label_type_in' => TextFilterType::TYPE_IN,
                        'oro.filter.form.label_type_not_in' => TextFilterType::TYPE_NOT_IN,
                        'oro.filter.form.label_type_empty' => FilterUtility::TYPE_EMPTY,
                        'oro.filter.form.label_type_not_empty' => FilterUtility::TYPE_NOT_EMPTY,
                    ]
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return array(
            'simple text' => array(
                'bindData' => array('type' => TextFilterType::TYPE_CONTAINS, 'value' => 'text'),
                'formData' => array('type' => TextFilterType::TYPE_CONTAINS, 'value' => 'text'),
                'viewData' => array(
                    'value' => array('type' => TextFilterType::TYPE_CONTAINS, 'value' => 'text'),
                ),
            ),
        );
    }
}
