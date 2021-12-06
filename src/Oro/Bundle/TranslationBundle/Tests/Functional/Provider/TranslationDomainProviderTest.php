<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationDomainProviderTest extends WebTestCase
{
    /** @var TranslationDomainProvider */
    private $provider;

    protected function setUp(): void
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

        /** @var TranslationKeyRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(TranslationKey::class);
        foreach ($repository->findAvailableDomains() as $domain) {
            $domains[] = ['code' => LoadLanguages::LANGUAGE2, 'domain' => $domain];
        }

        $actualDomains = $this->provider->getAvailableDomainsForLocales([LoadLanguages::LANGUAGE2]);
        $this->assertCount(count($domains), $actualDomains);

        foreach ($domains as $domain) {
            $this->assertContains($domain, $actualDomains);
        }
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

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(TranslationKey::class);
        $em->persist($key);
        $em->flush();

        $domains = $this->provider->clearCache()->getAvailableDomains();

        $this->assertGreaterThanOrEqual(2, count($domains));
        $this->assertContains($uniqueDomain, $domains);
    }
}
