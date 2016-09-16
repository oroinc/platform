<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
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
        $this->formExtensions[] = new CustomFormExtension(
            [
                new FilterType($translator),
                new TextFilterType($translator)
            ]
        );

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
        return [
            [
                'defaultOptions' => [
                    'operator_choices' => [
                        TextFilterType::TYPE_CONTAINS     => 'oro.filter.form.label_type_contains',
                        TextFilterType::TYPE_NOT_CONTAINS => 'oro.filter.form.label_type_not_contains',
                        TextFilterType::TYPE_EQUAL        => 'oro.filter.form.label_type_equals',
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
        return [
            'simple text' => [
                'bindData' => ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'text'],
                'formData' => ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'text'],
                'viewData' => [
                    'value' => ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'text'],
                ],
            ],
        ];
    }
}
