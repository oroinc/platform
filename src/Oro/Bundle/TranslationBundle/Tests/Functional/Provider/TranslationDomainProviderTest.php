<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationDomainProviderTest extends WebTestCase
{
    private TranslationDomainProvider $provider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->provider = self::getContainer()->get('oro_translation.provider.translation_domain');
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

    public function testGetAvailableDomains(): void
    {
        $domains = $this->provider->getAvailableDomains();

        self::assertContains('test_domain', $domains);
        self::assertGreaterThanOrEqual(1, count($domains));

        $uniqueDomain = uniqid('DOMAIN_', true);
        $uniqueKey = uniqid('KEY_', true);

        self::assertNotContains($uniqueDomain, $domains);

        $key = new TranslationKey();
        $key->setKey($uniqueKey)->setDomain($uniqueDomain);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(TranslationKey::class);
        $em->persist($key);
        $em->flush();

        $this->provider->clearCache();
        $domains = $this->provider->getAvailableDomains();

        self::assertGreaterThanOrEqual(2, count($domains));
        self::assertContains($uniqueDomain, $domains);
    }

    public function testGetAvailableDomainChoices(): void
    {
        $domains = $this->provider->getAvailableDomains();
        self::assertEquals(array_combine($domains, $domains), $this->provider->getAvailableDomainChoices());
    }
}
