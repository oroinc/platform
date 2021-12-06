<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Entity;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Tests\Functional\DataFixtures\LoadTagsData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TagTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadTagsData::class]);
    }

    public function testAddTaggingNotFetchAllTaggingCollection()
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(TestActivity::class);

        $entityManager->getUnitOfWork()->clear();
        $this->assertEmpty($entityManager->getUnitOfWork()->getIdentityMap());

        /** @var Tag $tag */
        $tag = $this->getReference(LoadTagsData::FIRST_TAG);
        $activity = $this->getReference(LoadTagsData::THIRD_ACTIVITY);

        $tagging = new Tagging($tag, $activity);
        $tag->addTagging($tagging);

        $identityMap = $entityManager->getUnitOfWork()->getIdentityMap();

        $this->assertArrayNotHasKey(Tagging::class, $identityMap);
        $this->assertEquals(3, $tag->getTagging()->count());
    }
}
