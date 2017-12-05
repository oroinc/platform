<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Provider;

use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityStructureDataProviderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testGetData()
    {
        /** @var EntityStructureDataProvider $provider */
        $provider = $this->getContainer()->get('oro_entity.provider.structure_data');
        $data = $provider->getData();

        $this->assertArrayHasKey(TestActivity::class, $data);
        $this->assertArrayNotHasKey(TestProduct::class, $data);
        $entityData = $data[TestActivity::class];

        // check aliases
        $this->assertEquals('testactivity', $entityData->getAlias());
        $this->assertEquals('testactivities', $entityData->getPluralAlias());

        $fields = $entityData->getFields();

        $field = $this->getField($fields, 'id');
        $this->assertNotNull($field);
        $this->assertTrue($field->getOption('identifier'));
        $this->assertTrue($field->getOption('configurable'));

        $field = $this->getField($fields, 'owner_id');
        $this->assertNotNull($field);
        $this->assertNull($field->getOption('configurable'));
        // check that labels translated
        $this->assertEquals('User', $field->getLabel());

        $field = $this->getField($fields, 'owner');
        $this->assertNotNull($field);
        $this->assertTrue($field->getOption('configurable'));
        // check extend entity
        $this->assertArrayHasKey('Extend\Entity\TestEntity1', $data);

        $fields = $data['Extend\Entity\TestEntity1']->getFields();
        $this->assertEquals(RelationType::MANY_TO_MANY, $this->getField($fields, 'biM2MNDTargets')->getRelationType());
        $this->assertEquals(RelationType::MANY_TO_ONE, $this->getField($fields, 'biM2OTarget')->getRelationType());
        $this->assertEquals(RelationType::ONE_TO_MANY, $this->getField($fields, 'uniO2MTargets')->getRelationType());

        // check relation types
        $this->assertEquals(RelationType::TO_ONE, $field->getType());
        $this->assertEquals(RelationType::MANY_TO_ONE, $field->getRelationType());

        // Check that we get only configurable entities
        $this->assertArrayNotHasKey(TestProduct::class, $data);
    }

    /**
     * @param array $fields
     * @param string $name
     * @return EntityFieldStructure
     */
    protected function getField(array $fields, $name)
    {
        foreach ($fields as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }
}
