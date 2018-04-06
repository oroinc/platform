<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Controller;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UIBundle\Route\Router;

/**
 * @dbIsolationPerTest
 */
class AttributeFamilyControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testCreate()
    {
        $this->loadFixtures([LoadAttributeData::class]);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_family_create', ['alias' => $this->getTestEntityAlias()])
        );
        $saveButton = $crawler->selectButton('Save and Close');

        $form = $saveButton->form();
        $form['oro_attribute_family[code]'] = 'AttributeFamilyCode';
        $form['oro_attribute_family[labels][values][default]'] = 'AttributeFamilyLabel';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Product Family was successfully saved', $crawler->html());
    }

    public function testUpdate()
    {
        $this->loadFixtures([LoadAttributeFamilyData::class]);

        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_family_update', ['id' => $attributeFamily->getId()])
        );

        $saveButton = $crawler->selectButton('Save and Close');

        $form = $saveButton->form();
        $form['oro_attribute_family[labels][values][default]'] = 'UpdatedAttributeFamilyLabel';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Successfully updated', $crawler->html());
    }

    /**
     * @param AttributeFamily $attributeFamily
     */
    private function assertFamilyIsNotDeleted(AttributeFamily $attributeFamily)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_family_delete', ['id' => $attributeFamily->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertContains('Can not be deleted', $result->getContent());
    }

    public function testDelete()
    {
        $this->loadFixtures([LoadAttributeFamilyData::class]);

        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);

        $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_family_delete', ['id' => $attributeFamily->getId()])
        );

        $result = $this->client->getResponse();

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertContains('Successfully deleted', $result->getContent());

        $lastAttributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $this->assertFamilyIsNotDeleted($lastAttributeFamily);
    }

    public function testDeleteWhenAttributeFamilyIsAssignedToEntity()
    {
        $this->loadFixtures([LoadAttributeFamilyData::class]);

        /** @var AttributeFamilyRepository $familyRepository */
        $familyRepository = $this->client->getContainer()->get('doctrine')->getRepository(AttributeFamily::class);

        /** @var AttributeFamily $family */
        $family = $familyRepository->findOneBy(['code' => LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2]);

        $manager = $this->client->getContainer()->get('doctrine')->getManagerForClass(TestActivityTarget::class);

        $testActivityTarget = new TestActivityTarget();
        $testActivityTarget->setAttributeFamily($family);

        $manager->persist($testActivityTarget);
        $manager->flush();

        $this->assertFamilyIsNotDeleted($family);
    }

    /**
     * @return string
     */
    protected function getTestEntityAlias()
    {
        return $this->getContainer()
            ->get('oro_entity.entity_alias_resolver')
            ->getAlias(TestActivityTarget::class);
    }
}
