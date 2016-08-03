<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManager;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class LocalizationProviderTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LocalizationRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData']);
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroLocaleBundle:Localization');
        $this->repository = $this->em->getRepository('OroLocaleBundle:Localization');
    }

    public function tearDown()
    {
        unset($this->em, $this->repository);
    }

    public function testCache()
    {
        $manager = $this->getContainer()->get('oro_locale.manager.localization');

        $queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());
        $prevLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();

        $this->em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $manager->getLocalizations();
        $data = $manager->getLocalizations();

        foreach ($data as $key => $localization) {
            $this->assertSame($key, $localization->getId());
            $this->assertSame($localization, $manager->getLocalization($localization->getId()));
        }

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(1, $queries);

        $this->assertNotEmpty($data);

        $this->em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }
}
