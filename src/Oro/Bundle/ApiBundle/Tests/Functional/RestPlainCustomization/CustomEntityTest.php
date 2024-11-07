<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlainCustomization;

use Extend\Entity\TestApiE1 as TestEntity1;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;

/**
 * @dbIsolationPerTest
 */
class CustomEntityTest extends RestPlainApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_entities.yml'
        ]);
    }

    private function renameTestEntity1Fields(): void
    {
        $this->appendEntityConfig(
            TestEntity1::class,
            [
                'fields' => [
                    'renamedName' => ['property_path' => 'name'],
                    'renamedSerializedField' => ['property_path' => 'serializedField'],
                    'renamedEnumField' => ['property_path' => 'enumField'],
                    'renamedMultiEnumField' => ['property_path' => 'multiEnumField'],
                    'renamedUniM2O' => ['property_path' => 'uniM2O'],
                    'renamedBiM2O' => ['property_path' => 'biM2O'],
                    'renamedUniM2M' => ['property_path' => 'uniM2M'],
                    'renamedDefaultUniM2M' => ['property_path' => 'default_uniM2M'],
                    'renamedUniM2MnD' => ['property_path' => 'uniM2MnD'],
                    'renamedBiM2M' => ['property_path' => 'biM2M'],
                    'renamedDefaultBiM2M' => ['property_path' => 'default_biM2M'],
                    'renamedBiM2MnD' => ['property_path' => 'biM2MnD'],
                    'renamedUniO2M' => ['property_path' => 'uniO2M'],
                    'renamedDefaultUniO2M' => ['property_path' => 'default_uniO2M'],
                    'renamedUniO2MnD' => ['property_path' => 'uniO2MnD'],
                    'renamedBiO2M' => ['property_path' => 'biO2M'],
                    'renamedDefaultBiO2M' => ['property_path' => 'default_biO2M'],
                    'renamedBiO2MnD' => ['property_path' => 'biO2MnD'],
                ]
            ],
            true
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => '@entity1_1->id']
        );

        $this->assertResponseContains(
            [
                'id' => '@entity1_1->id',
                'name' => 'Entity 1 (1)',
                'serializedField' => 'serialized 1',
                'enumField' => '@enum1_1->internalId',
                'multiEnumField' => ['@enum2_1->internalId', '@enum2_2->internalId'],
                'uniM2O' => '@entity2_1->id',
                'biM2O' => '@entity2_1->id',
                'uniM2M' => ['@entity2_1->id', '@entity2_2->id'],
                'default_uniM2M' => '@entity2_1->id',
                'uniM2MnD' => ['@entity2_1->id', '@entity2_2->id'],
                'biM2M' => ['@entity2_1->id', '@entity2_2->id'],
                'default_biM2M' => '@entity2_1->id',
                'biM2MnD' => ['@entity2_1->id', '@entity2_2->id'],
                'uniO2M' => ['@entity2_1->id', '@entity2_2->id'],
                'default_uniO2M' => '@entity2_1->id',
                'uniO2MnD' => ['@entity2_1->id', '@entity2_2->id'],
                'biO2M' => ['@entity2_1->id', '@entity2_2->id'],
                'default_biO2M' => '@entity2_1->id',
                'biO2MnD' => ['@entity2_1->id', '@entity2_2->id']
            ],
            $response
        );
    }

    public function testGetForRenamedFields(): void
    {
        $this->renameTestEntity1Fields();
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => '@entity1_1->id']
        );

        $this->assertResponseContains(
            [
                'id' => '@entity1_1->id',
                'renamedName' => 'Entity 1 (1)',
                'renamedSerializedField' => 'serialized 1',
                'renamedEnumField' => '@enum1_1->internalId',
                'renamedMultiEnumField' => ['@enum2_1->internalId', '@enum2_2->internalId'],
                'renamedUniM2O' => '@entity2_1->id',
                'renamedBiM2O' => '@entity2_1->id',
                'renamedUniM2M' => ['@entity2_1->id', '@entity2_2->id'],
                'renamedDefaultUniM2M' => '@entity2_1->id',
                'renamedUniM2MnD' => ['@entity2_1->id', '@entity2_2->id'],
                'renamedBiM2M' => ['@entity2_1->id', '@entity2_2->id'],
                'renamedDefaultBiM2M' => '@entity2_1->id',
                'renamedBiM2MnD' => ['@entity2_1->id', '@entity2_2->id'],
                'renamedUniO2M' => ['@entity2_1->id', '@entity2_2->id'],
                'renamedDefaultUniO2M' => '@entity2_1->id',
                'renamedUniO2MnD' => ['@entity2_1->id', '@entity2_2->id'],
                'renamedBiO2M' => ['@entity2_1->id', '@entity2_2->id'],
                'renamedDefaultBiO2M' => '@entity2_1->id',
                'renamedBiO2MnD' => ['@entity2_1->id', '@entity2_2->id']
            ],
            $response
        );
    }

    public function testGetListForEnumEntity(): void
    {
        $response = $this->cget(['entity' => 'testapienum1']);

        $this->assertResponseContains(
            [
                [
                    'id' => '0',
                    'name' => 'Item 0',
                    'priority' => -1,
                    'default' => true
                ],
                [
                    'id' => '1',
                    'name' => 'Item 1',
                    'priority' => 0,
                    'default' => false
                ],
                [
                    'id' => '2',
                    'name' => 'Item 2',
                    'priority' => 1,
                    'default' => false
                ],
                [
                    'id' => '3',
                    'name' => 'Item 3',
                    'priority' => 2,
                    'default' => false
                ],
                [
                    'id' => '4',
                    'name' => 'Item 4',
                    'priority' => 3,
                    'default' => false
                ]
            ],
            $response
        );
    }

    public function testGetForEnumEntity(): void
    {
        $response = $this->get(['entity' => 'testapienum1', 'id' => '0']);

        $this->assertResponseContains(
            [
                'id' => '0',
                'name' => 'Item 0',
                'priority' => -1,
                'default' => true
            ],
            $response
        );
    }
}
