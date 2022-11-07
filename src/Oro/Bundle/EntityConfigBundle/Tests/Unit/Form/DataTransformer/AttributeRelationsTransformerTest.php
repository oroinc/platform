<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Form\DataTransformer\AttributeRelationsTransformer;

class AttributeRelationsTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeRelationsTransformer */
    private $dataTransformer;

    protected function setUp(): void
    {
        $this->dataTransformer = new AttributeRelationsTransformer(null);
    }

    public function transformDataProvider(): array
    {
        $relation = new AttributeGroupRelation();
        $relation->setEntityConfigFieldId(777);
        $attributeGroup = new AttributeGroup();
        $attributeGroup->addAttributeRelation($relation);

        return [
            [
                'collection' => null,
                'expectation' => [],
            ],
            [
                'collection' => (new AttributeGroup)->getAttributeRelations(),
                'expectation' => [],
            ],
            [
                'collection' => $attributeGroup->getAttributeRelations(),
                'expectation' => [777],
            ],
        ];
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?Collection $collection, array $expectation): void
    {
        $this->assertEquals($expectation, $this->dataTransformer->transform($collection));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            'new group with no attributes selected' => [
                'attributeGroup' => null,
                'selectedAttributes' => [],
                'expectation' => new ArrayCollection(),
            ],
            'new group with attributes selected' => [
                'attributeGroup' => null,
                'selectedAttributes' => [333],
                'expectation' => (new AttributeGroup)
                    ->addAttributeRelation(
                        (new AttributeGroupRelation())->setEntityConfigFieldId(333)
                    )
                    ->getAttributeRelations(),
            ],
            'existing group with all attributes removed' => [
                'attributeGroup' => (new AttributeGroup)
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(111))
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(333)),
                'selectedAttributes' => [],
                'expectation' => new ArrayCollection(),
            ],
            'existing group with new attribute added' => [
                'attributeGroup' => (new AttributeGroup)
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(777)),
                'selectedAttributes' => [111, 777],
                'expectation' => (new AttributeGroup)
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(111))
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(777))
                    ->getAttributeRelations(),
            ],
            'existing group with attributes order changed' => [
                'attributeGroup' => (new AttributeGroup)
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(111))
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(333)),
                'selectedAttributes' => [333, 111],
                'expectation' => (new AttributeGroup)
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(333))
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(111))
                    ->getAttributeRelations(),
            ],
            'existing group with attributes changed' => [
                'attributeGroup' => (new AttributeGroup)
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(111))
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(333)),
                'selectedAttributes' => [777, 333],
                'expectation' => (new AttributeGroup)
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(777))
                    ->addAttributeRelation((new AttributeGroupRelation())->setEntityConfigFieldId(333))
                    ->getAttributeRelations(),
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(
        ?AttributeGroup $attributeGroup,
        array $selectedAttributes,
        Collection $expectation
    ): void {
        $transformer = new AttributeRelationsTransformer($attributeGroup);
        $this->assertEquals($expectation, $transformer->reverseTransform($selectedAttributes));
    }
}
