<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManager;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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

    public function testCache()
    {
        $this->markTestSkipped('Skipped because should be fixed at BAP-14732');

        $manager = $this->getContainer()->get('oro_locale.manager.localization');

        $queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());
        $prevLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();

        //warm up cache
        $manager->getLocalizations();

        $this->em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);
        //data should be restored from cache
        $data = $manager->getLocalizations();

        foreach ($data as $key => $localization) {
            $this->assertEquals($key, $localization->getId());
            $this->assertEquals($localization, $manager->getLocalization($localization->getId()));
        }

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(0, $queries);

        $this->assertNotEmpty($data);

        $this->em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }
}
