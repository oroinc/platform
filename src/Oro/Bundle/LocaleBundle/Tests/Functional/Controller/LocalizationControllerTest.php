<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocalizationControllerTest extends WebTestCase
{
    use OperationAwareTestTrait;

    private const NAME = 'Localization name';
    private const DEFAULT_TITLE = 'Default localization title';
    private const LANGUAGE_CODE = 'es_MX';
    private const FORMATTING_CODE = 'es_MX';

    private const UPDATED_NAME = 'Updated name';
    private const UPDATED_DEFAULT_TITLE = 'Updated default title';
    private const UPDATED_ALTERED_TITLE = 'Updated altered title';
    private const UPDATED_LANGUAGE_CODE = 'es_ES';
    private const UPDATED_FORMATTING_CODE = 'es_ES';
    private const PARENT_LOCALIZATION = 'es';

    /** @var LocalizationManager */
    private $manager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadLocalizationData::class]);

        $this->manager = $this->getContainer()->get('oro_locale.manager.localization');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_locale_localization_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('oro-locale-localizations-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_locale_localization_create'));

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_localization']['name'] = self::NAME;
        $formValues['oro_localization']['titles']['values']['default'] = self::DEFAULT_TITLE;
        $formValues['oro_localization']['language'] = $this->getReference('language.' . self::LANGUAGE_CODE)->getId();
        $formValues['oro_localization']['formattingCode'] = self::FORMATTING_CODE;

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $localization = $this->getLocalization(self::NAME);
        $this->assertInstanceOf(Localization::class, $localization);

        $localizationId = $localization->getId();
        $cachedLocalization = $this->manager->getLocalization($localizationId);
        $this->assertInstanceOf(Localization::class, $cachedLocalization);
        $this->assertEquals($localizationId, $cachedLocalization->getId());

        $html = $crawler->html();
        self::assertStringContainsString('Localization has been saved', $html);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(): int
    {
        /** @var Localization $localization */
        $localization = $this->getLocalization(self::NAME);
        $this->assertNotEmpty($localization);

        $id = $localization->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_locale_localization_update', ['id' => $id]));

        $html = $crawler->html();

        self::assertStringContainsString(self::NAME, $html);
        self::assertStringContainsString(self::DEFAULT_TITLE, $html);
        self::assertStringContainsString(self::LANGUAGE_CODE, $html);
        self::assertStringContainsString($this->getFormattingFormatter()->format(self::FORMATTING_CODE), $html);

        $form = $crawler->selectButton('Save and Close')->form();

        /** @var Localization $parent */
        $parent = $this->getReference(self::PARENT_LOCALIZATION);

        $formValues = $form->getPhpValues();
        $formValues['oro_localization']['name'] = self::UPDATED_NAME;
        $formValues['oro_localization']['titles']['values']['default'] = self::UPDATED_DEFAULT_TITLE;

        foreach ($formValues['oro_localization']['titles']['values']['localizations'] as $localeId => $value) {
            if ($value['fallback'] === FallbackType::PARENT_LOCALIZATION) {
                $value['fallback'] = FallbackType::SYSTEM;

                $formValues['oro_localization']['titles']['values']['localizations'][$localeId] = $value;
            }
        }

        $formValues['oro_localization']['language'] = $this->getReference('language.' . self::UPDATED_LANGUAGE_CODE)
            ->getId();
        $formValues['oro_localization']['formattingCode'] = self::UPDATED_FORMATTING_CODE;
        $formValues['oro_localization']['parentLocalization'] = $parent->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();
        self::assertStringContainsString('Localization has been saved', $html);

        $localizationId = $this->getLocalization(self::UPDATED_NAME)->getId();
        $cachedLocalization = $this->manager->getLocalization($localizationId);
        $this->assertInstanceOf(Localization::class, $cachedLocalization);
        $this->assertEquals($localizationId, $cachedLocalization->getId());
        $this->assertEquals(self::UPDATED_LANGUAGE_CODE, $cachedLocalization->getLanguageCode());

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView(int $id): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_locale_localization_view', ['id' => $id]));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Localization $parent */
        $parent = $this->getReference(self::PARENT_LOCALIZATION);

        $html = $crawler->html();
        self::assertStringContainsString(self::UPDATED_NAME, $html);
        self::assertStringContainsString(self::UPDATED_DEFAULT_TITLE, $html);
        self::assertStringContainsString(
            $this->getLanguageFormatter()->formatLocale(self::UPDATED_LANGUAGE_CODE),
            $html
        );
        self::assertStringContainsString(
            $this->getFormattingFormatter()->format(self::UPDATED_FORMATTING_CODE),
            $html
        );
        self::assertStringContainsString($parent->getName(), $html);

        return $id;
    }

    /**
     * @depends testView
     */
    public function testDelete(int $id)
    {
        $operationName = 'DELETE';
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'entityId' => $id,
                    'entityClass' => Localization::class
                ]
            ),
            $this->getOperationExecuteParams($operationName, $id, Localization::class),
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('oro_locale_localization_index'),
                'pageReload' => true
            ],
            json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );

        $this->client->request('GET', $this->getUrl('oro_locale_localization_view', ['id' => $id]));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);

        $cachedLocalization = $this->manager->getLocalization($id);
        $this->assertNull($cachedLocalization);
    }

    private function getLanguageFormatter(): LanguageCodeFormatter
    {
        return $this->getContainer()->get('oro_locale.formatter.language_code');
    }

    private function getFormattingFormatter(): FormattingCodeFormatter
    {
        return $this->getContainer()->get('oro_locale.formatter.formatting_code');
    }

    private function getLocalization(string $name): ?Localization
    {
        return $this->getRepository()->findOneBy(['name' => $name]);
    }

    private function getRepository(): LocalizationRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Localization::class);
    }
}
