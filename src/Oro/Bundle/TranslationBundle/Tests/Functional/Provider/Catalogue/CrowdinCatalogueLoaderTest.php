<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Provider\Catalogue;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Provider\Catalogue\CrowdinCatalogueLoader;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Download\TranslationServiceAdapterStub;
use Oro\Component\Testing\ReflectionUtil;

class CrowdinCatalogueLoaderTest extends WebTestCase
{
    /** @var CrowdinCatalogueLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->initClient();

        // change the translationServiceAdapter at the command instance to the stub
        // to be able to work with local archive instead of real calls to the remote service.
        $archivePath = realpath(__DIR__ . '/../../Stub/translations.zip');
        $translationServiceAdapter = new TranslationServiceAdapterStub($archivePath);
        $this->loader = self::getContainer()->get('oro_translation.catalogue_loader.crowdin');
        ReflectionUtil::setPropertyValue($this->loader, 'translationServiceAdapter', $translationServiceAdapter);
    }

    public function testDumpWithFrLocale(): void
    {
        $catalogue = $this->loader->getCatalogue('fr_FR');
        $domains = $catalogue->getDomains();
        self::assertCount(6, $domains);
        self::assertContains('config', $domains);
        self::assertContains('validators', $domains);
        self::assertContains('security', $domains);
        self::assertContains('messages', $domains);
        self::assertContains('jsmessages', $domains);
        self::assertEquals(
            'Nom d\'utilisateur ou mot de passe invalide.',
            $catalogue->get('Invalid credentials.', 'security')
        );
        self::assertEquals('Supprimer', $catalogue->get('Delete'));
        self::assertEquals('Utilisateur', $catalogue->get('entity.user.name', 'config'));
    }

    public function testDumpWithEnLocale(): void
    {
        $catalogue = $this->loader->getCatalogue('en');
        $domains = $catalogue->getDomains();
        self::assertCount(4, $domains);
        self::assertContains('config', $domains);
        self::assertContains('validators', $domains);
        self::assertContains('messages', $domains);
        self::assertContains('jsmessages', $domains);
        self::assertEquals('Invalid credentials.', $catalogue->get('Invalid credentials.', 'security'));
        self::assertEquals('Delete', $catalogue->get('Delete'));
        self::assertEquals('User', $catalogue->get('entity.user.name', 'config'));
    }
}
