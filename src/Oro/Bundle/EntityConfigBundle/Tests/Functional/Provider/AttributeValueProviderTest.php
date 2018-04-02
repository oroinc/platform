<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProviderInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;

class AttributeValueProviderTest extends WebTestCase
{
    /**
     * @var AttributeValueProviderInterface
     */
    protected $provider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            LoadAttributeFamilyData::class,
            LoadActivityTargets::class,
        ]);
        $this->provider = $this->getContainer()->get('oro_entity_config.provider.attribute_value');
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
    }

    public function testRemoveAttributeValues()
    {
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        
        $testActivityTargetManager = $this->doctrineHelper->getEntityManagerForClass(TestActivityTarget::class);
        $testActivityTarget = $this->loadTestActivityTarget($attributeFamily, $testActivityTargetManager);
        $this->assertNotEmpty($testActivityTarget->getString());
        
        $this->provider->removeAttributeValues(
            $attributeFamily,
            ['string']
        );

        $testActivityTargetManager->refresh($testActivityTarget);
        $this->assertEmpty($testActivityTarget->getString());
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param EntityManagerInterface $manager
     * @return TestActivityTarget
     */
    protected function loadTestActivityTarget(AttributeFamily $attributeFamily, EntityManagerInterface $manager)
    {
        $testActivityTarget = $this->getReference('activity_target_one');
        $testActivityTarget->setString('some string');
        $testActivityTarget->setAttributeFamily($attributeFamily);

        $manager->persist($testActivityTarget);
        $manager->flush();
        
        return $testActivityTarget;
    }
}
