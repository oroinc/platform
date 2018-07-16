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

    protected function setUp()
    {
        $this->dataTransformer = new AttributeRelationsTransformer(null);
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        $relation = new AttributeGroupRelation();
        $relation->setEntityConfigFieldId(777);
        $attributeGroup = new AttributeGroup();
        $attributeGroup->addAttributeRelation($relation);

        return [
            [
                'collection' => null,
                'expectation' => []
            ],
            [
                'collection' => (new AttributeGroup)->getAttributeRelations(),
                'expectation' => []
            ],
            [
                'collection' => $attributeGroup->getAttributeRelations(),
                'expectation' => [777]
            ],
        ];
    }

    /**
     * @dataProvider transformDataProvider
     * @param Collection|null $collection
     * @param array $expectation
     */
    public function testTransform($collection, array $expectation)
    {
        $this->assertEquals($expectation, $this->dataTransformer->transform($collection));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        $relation1 = new AttributeGroupRelation();
        $relation1->setEntityConfigFieldId(111);
        $relation2 = new AttributeGroupRelation();
        $relation2->setEntityConfigFieldId(333);

        $existingGroup1 = new AttributeGroup();
        $existingGroup1->addAttributeRelation($relation1)->addAttributeRelation($relation2);

        $existingGroup2 = new AttributeGroup();
        $existingGroup2->addAttributeRelation($relation1);

        $newRelation = new AttributeGroupRelation();
        $newRelation->setEntityConfigFieldId(777);
        $newRelation->setAttributeGroup($existingGroup2);

        return [
            'new group with no attributes selected' => [
                'attributeGroup' => null,
                'selectedAttributes' => [],
                'expectation' => (new AttributeGroup)->getAttributeRelations()
            ],
            'new group with attributes selected' => [
                'attributeGroup' => null,
                'selectedAttributes' => [333],
                'expectation' => (new AttributeGroup)->addAttributeRelation($relation2)->getAttributeRelations()
            ],
            'existing group with all attributes removed' => [
                'attributeGroup' => $existingGroup1,
                'selectedAttributes' => [],
                'expectation' => new ArrayCollection()
            ],
            'existing group with new attribute added' => [
                'attributeGroup' => $existingGroup2,
                'selectedAttributes' => [111, 777],
                'expectation' => new ArrayCollection([$relation1, $newRelation])
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     * @param \Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup|null $attributeGroup
     * @param array $selectedAttributes
     * @param Collection $expectation
     */
    public function testReverseTransform($attributeGroup, array $selectedAttributes, Collection $expectation)
    {
        $transformer = new AttributeRelationsTransformer($attributeGroup);
        $this->assertEquals($expectation, $transformer->reverseTransform($selectedAttributes));
    }
}
