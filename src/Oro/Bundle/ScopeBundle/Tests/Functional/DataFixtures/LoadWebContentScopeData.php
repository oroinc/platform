<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * Creates a scope for web content with anonymous customer group, default website, localization, and webcatalog.
 */
class LoadWebContentScopeData extends AbstractFixture implements DependentFixtureInterface
{
    public const string WEB_CONTENT_SCOPE = 'web_content_scope';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadGroups::class,
            LoadWebsiteData::class,
            LoadLocalizationData::class,
            LoadWebCatalogData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $scope = new Scope();
        $scope->setCustomerGroup($this->getReference(LoadGroups::ANONYMOUS_GROUP));
        $scope->setLocalization($this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE));
        $scope->setWebCatalog($this->getReference(LoadWebCatalogData::CATALOG_1));

        $manager->persist($scope);
        $manager->flush();
        $this->setReference(self::WEB_CONTENT_SCOPE, $scope);
    }
}
