<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationDomainProviderTest extends WebTestCase
{
    /** @var TranslationDomainProvider */
    protected $provider;

    protected function setUp()
    {
        $this->initClient();

        $this->provider = $this->getContainer()->get('oro_translation.provider.translation_domain');

        $this->loadFixtures([LoadTranslations::class]);
    }

    /**
     * {@inheritdoc}
     */
    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();

        $this->provider->clearCache();
    }

    public function testGetAvailableDomainsForLocales()
    {
        $domains = [];

        foreach ($this->getRepository()->findAvailableDomains() as $domain) {
            $domains[] = ['code' => LoadLanguages::LANGUAGE2, 'domain' => $domain];
        }

        $this->assertEquals($domains, $this->provider->getAvailableDomainsForLocales([LoadLanguages::LANGUAGE2]));
    }

    public function testGetAvailableDomains()
    {
        $domains = $this->provider->getAvailableDomains();

        $this->assertContains('test_domain', $domains);
        $this->assertGreaterThanOrEqual(1, count($domains));

        $uniqueDomain = uniqid('DOMAIN_', true);
        $uniqueKey = uniqid('KEY_', true);

        $this->assertNotContains($uniqueDomain, $domains);

        $key = new TranslationKey();
        $key->setKey($uniqueKey)->setDomain($uniqueDomain);

        $manager = $this->getManager();
        $manager->persist($key);
        $manager->flush();

        $domains = $this->provider->clearCache()->getAvailableDomains();

        $this->assertGreaterThanOrEqual(2, count($domains));
        $this->assertContains($uniqueDomain, $domains);
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(TranslationKey::class);
    }

    /**
     * @return TranslationKeyRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository(TranslationKey::class);
    }
}
