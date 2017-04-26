<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AttributeFamilyRepositoryTest extends WebTestCase
{
    /**
     * @var AttributeFamilyRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadAttributeFamilyData::class,
        ]);

        $this->repository = $this
            ->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(AttributeFamily::class);
    }

    public function testGetFamiliesByAttributeIdEmpty()
    {
        $families = $this->repository->getFamiliesByAttributeId(
            LoadAttributeData::getAttributeIdByName(LoadAttributeData::NOT_USED_ATTRIBUTE)
        );

        $this->assertCount(0, $families);
    }

    public function testGetFamiliesByAttributeId()
    {
        $families = $this->repository->getFamiliesByAttributeId(
            LoadAttributeData::getAttributeIdByName(LoadAttributeData::SYSTEM_ATTRIBUTE_1)
        );

        $this->assertCount(2, $families);
        $this->assertInstanceOf(AttributeFamily::class, reset($families));
        $this->assertEquals($this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1), $families[0]);
        $this->assertEquals($this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2), $families[1]);
    }

    public function testCountFamiliesByEntityClass()
    {
        $this->assertEquals(2, $this->repository->countFamiliesByEntityClass(
            LoadAttributeData::ENTITY_CONFIG_MODEL
        ));
    }

    public function testCountFamiliesByEntityClassWithNotExistingEntityClass()
    {
        $this->assertEquals(0, $this->repository->countFamiliesByEntityClass('NotExistingEntityClass'));
    }
}
