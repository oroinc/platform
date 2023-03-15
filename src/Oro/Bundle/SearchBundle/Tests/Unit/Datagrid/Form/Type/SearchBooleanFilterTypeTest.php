<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchBooleanFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchBooleanFilterTypeTest extends AbstractTypeTestCase
{
    /** @var SearchBooleanFilterType */
    private $type;

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();
        $this->type = new SearchBooleanFilterType();
        $this->formExtensions[] = new CustomFormExtension(
            [
                new BooleanFilterType(),
                new ChoiceFilterType($translator),
                new FilterType($translator),
                $this->type
            ]
        );
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType(): AbstractType
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
    public function configureOptionsDataProvider(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider(): array
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

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_search_type_boolean_filter', $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(BooleanFilterType::class, $this->type->getParent());
    }
}
