<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchStringFilterType;

class SearchStringFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var SearchStringFilterType
     */
    private $type;

    protected function setUp()
    {
        $translator             = $this->createMockTranslator();
        $this->formExtensions[] = new CustomFormExtension(array(new FilterType($translator)));

        parent::setUp();
        $this->type = new SearchStringFilterType($translator);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    public function testGetName()
    {
        $this->assertEquals(SearchStringFilterType::NAME, $this->type->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptionsDataProvider()
    {
        return array(
            array(
                'defaultOptions' => array(
                    'field_type'       => 'text',
                    'operator_choices' => array(
                        SearchStringFilterType::TYPE_CONTAINS     => 'oro.filter.form.label_type_contains',
                        SearchStringFilterType::TYPE_NOT_CONTAINS => 'oro.filter.form.label_type_not_contains',
                        SearchStringFilterType::TYPE_EQUAL        => 'oro.filter.form.label_type_equals',
                    )
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
            'simple text' => array(
                'bindData' => array('type' => SearchStringFilterType::TYPE_CONTAINS, 'value' => 'text'),
                'formData' => array('type' => SearchStringFilterType::TYPE_CONTAINS, 'value' => 'text'),
                'viewData' => array(
                    'value' => array('type' => SearchStringFilterType::TYPE_CONTAINS, 'value' => 'text'),
                ),
            ),
        );
    }
}
