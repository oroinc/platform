<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository;

/**
 * @dbIsolation
 */
class LocalizationRepositoryTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LocaleRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData']);
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroLocaleBundle:Localization');
        $this->repository = $this->em->getRepository('OroLocaleBundle:Localization');
    }

    public function testFindRootsWithChildren()
    {
        $localizations = [$this->getCurrentLocalization(), $this->getReference('en_US')];
        $queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());

        $prevLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();
        $this->em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        /** @var Locaization[] $result */
        $result = $this->repository->findRootsWithChildren();

        $this->assertEquals($localizations, $result);

        foreach ($result as $root) {
            $this->visitChildren($root);
        }

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(1, $queries);

        $this->em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    /**
     * @param Localization $localization
     */
    protected function visitChildren(Localization $localization)
    {
        $localization->getLanguageCode();
        foreach ($localization->getChilds() as $child) {
            $this->visitChildren($child);
        }
    }

    /**
     * @return null|Localization
     */
    protected function getCurrentLocalization()
    {
        /* @var $localeSettings LocaleSettings */
        $localeSettings = $this->getContainer()->get('oro_locale.settings');
        return $this->repository->findOneByLanguageCode($localeSettings->getLocale());
    }
}
