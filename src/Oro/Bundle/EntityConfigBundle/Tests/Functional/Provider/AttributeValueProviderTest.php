<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProviderInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;

class AttributeValueProviderTest extends WebTestCase
{
    /** @var AttributeValueProviderInterface */
    private $provider;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadAttributeFamilyData::class,
            LoadActivityTargets::class,
        ]);

        $this->provider = self::getContainer()->get('oro_entity_config.provider.attribute_value');
    }

    public function testRemoveAttributeValues()
    {
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(TestActivityTarget::class);
        $testActivityTarget = $this->loadTestActivityTarget($attributeFamily, $em);
        $this->assertNotEmpty($testActivityTarget->getString());

        $this->provider->removeAttributeValues(
            $attributeFamily,
            ['string']
        );

        $em->refresh($testActivityTarget);
        $this->assertEmpty($testActivityTarget->getString());
    }

    private function loadTestActivityTarget(
        AttributeFamily $attributeFamily,
        EntityManagerInterface $manager
    ): TestActivityTarget {
        $testActivityTarget = $this->getReference('activity_target_one');
        $testActivityTarget->setString('some string');
        $testActivityTarget->setAttributeFamily($attributeFamily);

        $manager->persist($testActivityTarget);
        $manager->flush();

        return $testActivityTarget;
    }
}
