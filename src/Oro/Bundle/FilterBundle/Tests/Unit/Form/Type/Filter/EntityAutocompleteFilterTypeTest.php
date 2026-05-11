<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityAutocompleteFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class EntityAutocompleteFilterTypeTest extends AbstractTypeTestCase
{
    private EntityAutocompleteFilterType $type;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createTranslator();

        $select2Type = $this->getMockBuilder(OroJquerySelect2HiddenType::class)
            ->onlyMethods(['createDefaultTransformer'])
            ->setConstructorArgs([
                $this->createMock(ManagerRegistry::class),
                $this->createMock(SearchRegistry::class),
                $this->createMock(ConfigProvider::class),
            ])
            ->getMock();
        $select2Type->method('createDefaultTransformer')
            ->willReturn(new class () implements DataTransformerInterface {
                public function transform(mixed $value): mixed
                {
                    return $value;
                }

                public function reverseTransform(mixed $value): mixed
                {
                    return $value ?: null;
                }
            });

        $this->type = new EntityAutocompleteFilterType();

        $this->formExtensions[] = new CustomFormExtension([
            new FilterType($translator),
            new ChoiceFilterType($translator),
            new Select2Type(HiddenType::class, 'oro_select2_hidden'),
        ]);
        $this->formExtensions[] = new PreloadedExtension(
            [OroJquerySelect2HiddenType::class => $select2Type, $this->type],
            []
        );

        parent::setUp();
    }

    #[\Override]
    protected function getTestFormType(): AbstractType
    {
        return $this->type;
    }

    public function testGetParent(): void
    {
        self::assertEquals(ChoiceFilterType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_type_entity_autocomplete_filter', $this->type->getBlockPrefix());
    }

    #[\Override]
    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'defaultOptions' => [
                    'field_type'    => OroJquerySelect2HiddenType::class,
                    'field_options' => [],
                ],
            ],
        ];
    }

    #[\Override]
    public function bindDataProvider(): array
    {
        $converter = new class () implements ConverterInterface {
            public function convertItem(mixed $item): array
            {
                return [];
            }
        };
        $fieldOptions = [
            'entity_class' => \stdClass::class,
            'converter'    => $converter,
            'configs'      => ['route_name' => 'test_route'],
        ];

        return [
            'empty' => [
                'bindData'      => [],
                'formData'      => ['type' => null, 'value' => null],
                'viewData'      => [
                    'value' => ['type' => null, 'value' => null],
                ],
                'customOptions' => ['field_options' => $fieldOptions],
            ],
            'not empty type' => [
                'bindData'      => ['type' => ChoiceFilterType::TYPE_CONTAINS],
                'formData'      => ['type' => ChoiceFilterType::TYPE_CONTAINS, 'value' => null],
                'viewData'      => [
                    'value' => ['type' => ChoiceFilterType::TYPE_CONTAINS, 'value' => null],
                ],
                'customOptions' => ['field_options' => $fieldOptions],
            ],
        ];
    }
}
