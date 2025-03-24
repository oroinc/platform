<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;

class EntityFilterTypeTest extends AbstractTypeTestCase
{
    private EntityFilterType $type;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createTranslator();
        $this->type = new EntityFilterType($translator);

        $this->formExtensions[] = new CustomFormExtension([
            new FilterType($translator),
            new ChoiceFilterType($translator),
            new EntityType($this->createMock(ManagerRegistry::class))
        ]);

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

    #[\Override]
    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'defaultOptions' => [
                    'field_type' => EntityType::class,
                    'field_options' => [],
                    'translatable'  => false,
                ]
            ]
        ];
    }

    /**
     * @dataProvider bindDataProvider
     */
    #[\Override]
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = []
    ): void {
        // bind method should be tested in functional test
    }

    #[\Override]
    public function bindDataProvider(): array
    {
        return [];
    }
}
