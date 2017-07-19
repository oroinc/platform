<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Entity;

use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Tests\Functional\DataFixtures\LoadTagsData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TagManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([LoadTagsData::class]);
    }

    public function testGetPreparedArrayAndEnsureTaggingsLoadedOnlyForGivenEntity()
    {
        $entityManager = static::getContainer()->get('doctrine')->getManagerForClass(TestActivity::class);
        $tagManager = static::getContainer()->get('oro_tag.tag.manager');

        $activity = $this->getReference(LoadTagsData::FIRST_ACTIVITY);

        $entityManager->getUnitOfWork()->clear();
        $this->assertEmpty($entityManager->getUnitOfWork()->getIdentityMap());

        $tagManager->getPreparedArray($activity);

        $identityMap = $entityManager->getUnitOfWork()->getIdentityMap();

        $this->assertCount(1, $identityMap[Tagging::class]);
        $this->assertSame($this->getReference('firstActivity.firstTag'), reset($identityMap[Tagging::class]));
    }
}
