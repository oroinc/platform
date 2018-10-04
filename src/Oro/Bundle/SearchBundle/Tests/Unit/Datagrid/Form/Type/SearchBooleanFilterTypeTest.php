<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchBooleanFilterType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchBooleanFilterTypeTest extends AbstractTypeTestCase
{
    /**
     * @var SearchBooleanFilterType
     */
    private $type;

    protected function setUp()
    {
        $translator = $this->createMockTranslator();
        $this->type = new SearchBooleanFilterType($translator);
        $this->formExtensions[] = new CustomFormExtension(
            [
                new BooleanFilterType($translator),
                new ChoiceFilterType($translator),
                new FilterType($translator),
                $this->type
            ],
            []
        );
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    public function testFormConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined('field_options');
        $this->type->configureOptions($resolver);

        $result = $resolver->resolve(['field_options' => []]);
        self::assertArrayHasKey('multiple', $result['field_options']);
        self::assertTrue($result['field_options']['multiple']);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return [
            'yes' => [
                'bindData' => ['type' => null, 'value' => [BooleanFilterType::TYPE_YES]],
                'formData' => ['type' => null, 'value' => [BooleanFilterType::TYPE_YES]],
                'viewData' => [
                    'value' => ['type' => null, 'value' => [BooleanFilterType::TYPE_YES]],
                ],
            ],
            'no' => [
                'bindData' => ['type' => null, 'value' => [BooleanFilterType::TYPE_NO]],
                'formData' => ['type' => null, 'value' => [BooleanFilterType::TYPE_NO]],
                'viewData' => [
                    'value' => ['type' => null, 'value' => [BooleanFilterType::TYPE_NO]],
                ],
            ],
            'both' => [
                'bindData' => ['type' => null, 'value' => [BooleanFilterType::TYPE_YES, BooleanFilterType::TYPE_NO]],
                'formData' => ['type' => null, 'value' => [BooleanFilterType::TYPE_YES, BooleanFilterType::TYPE_NO]],
                'viewData' => [
                    'value' => ['type' => null, 'value' => [BooleanFilterType::TYPE_YES, BooleanFilterType::TYPE_NO]],
                ],
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(SearchBooleanFilterType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SearchBooleanFilterType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(BooleanFilterType::class, $this->type->getParent());
    }
}
