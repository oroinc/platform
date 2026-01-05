<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taxonomy;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadTaxonomyWithTagsData extends AbstractFixture
{
    public const FIRST_TAG = 'first_tag';
    public const SECOND_TAG = 'second_tag';
    public const THIRD_TAG = 'third_tag';

    public const FIRST_TAXONOMY = 'first_taxonomy';
    public const SECOND_TAXONOMY = 'second_taxonomy';
    public const THIRD_TAXONOMY = 'third_taxonomy';

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->loadTaxonomies($manager);
        $this->loadTags($manager);

        $manager->flush();
    }

    private function loadTags(ObjectManager $manager): void
    {
        $tags = [
            self::FIRST_TAXONOMY => self::FIRST_TAG,
            self::SECOND_TAXONOMY => self::SECOND_TAG,
            self::THIRD_TAXONOMY => self::THIRD_TAG
        ];

        foreach ($tags as $taxonomyName => $tagName) {
            $tag = new Tag();
            $tag->setName($tagName);
            $tag->setTaxonomy($this->getReference($taxonomyName));
            $this->addReference($tagName, $tag);
            $manager->persist($tag);
        }
    }

    private function loadTaxonomies(ObjectManager $manager): void
    {
        $taxonomies = [self::FIRST_TAXONOMY, self::SECOND_TAXONOMY, self::THIRD_TAXONOMY];

        foreach ($taxonomies as $taxonomyName) {
            $taxonomy = new Taxonomy();
            $taxonomy->setName($taxonomyName);
            $taxonomy->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $taxonomy->setOwner($this->getReference(LoadUser::USER));
            $this->addReference($taxonomyName, $taxonomy);
            $manager->persist($taxonomy);
        }
    }
}
