<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchStringFilterType;
use Oro\Component\Testing\Unit\PreloadedExtension;

class SearchStringFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var SearchStringFilterType
     */
    private $type;

    protected function setUp()
    {
        $translator             = $this->createMockTranslator();
        $this->type = new SearchStringFilterType($translator);

        $this->formExtensions[] = new CustomFormExtension(
            [
                new FilterType($translator),
                new TextFilterType($translator)
            ]
        );
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

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SearchStringFilterType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(TextFilterType::class, $this->type->getParent());
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider()
    {
        return [
            [
                'defaultOptions' => [
                    'operator_choices' => [
                        'oro.filter.form.label_type_contains'=> TextFilterType::TYPE_CONTAINS,
                        'oro.filter.form.label_type_not_contains' => TextFilterType::TYPE_NOT_CONTAINS,
                        'oro.filter.form.label_type_equals' => TextFilterType::TYPE_EQUAL,
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
