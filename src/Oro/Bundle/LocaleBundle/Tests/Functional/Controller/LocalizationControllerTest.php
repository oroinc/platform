<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class LocalizationControllerTest extends WebTestCase
{
    const NAME = 'Localization name';
    const DEFAULT_TITLE = 'Default localization title';
    const LANGUAGE_CODE = 'es_MX';
    const FORMATTING_CODE = 'es_MX';

    const UPDATED_NAME = 'Updated name';
    const UPDATED_DEFAULT_TITLE = 'Updated default title';
    const UPDATED_ALTERED_TITLE = 'Updated altered title';
    const UPDATED_LANGUAGE_CODE = 'es_ES';
    const UPDATED_FORMATTING_CODE = 'es_ES';
    const PARENT_LOCALIZATION = 'es';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData']);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_locale_localization_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('oro-locale-localizations-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_locale_localization_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_localization']['name'] = self::NAME;
        $formValues['oro_localization']['titles']['values']['default'] = self::DEFAULT_TITLE;
        $formValues['oro_localization']['languageCode'] = self::LANGUAGE_CODE;
        $formValues['oro_localization']['formattingCode'] = self::FORMATTING_CODE;

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();
        $this->assertContains('Localization has been saved', $html);
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testUpdate()
    {
        /** @var Localization $localization */
        $localization = $this->getLocalization(self::NAME);
        $this->assertNotEmpty($localization);

        $id = $localization->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_locale_localization_update', ['id' => $id]));

        $html = $crawler->html();
        
        $this->assertContains(self::NAME, $html);
        $this->assertContains(self::DEFAULT_TITLE, $html);
        $this->assertContains($this->getLanguageFormatter()->format(self::LANGUAGE_CODE), $html);
        $this->assertContains($this->getFormattingFormatter()->format(self::FORMATTING_CODE), $html);

        /** @var Form $form */
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

        $formValues['oro_localization']['languageCode'] = self::UPDATED_LANGUAGE_CODE;
        $formValues['oro_localization']['formattingCode'] = self::UPDATED_FORMATTING_CODE;
        $formValues['oro_localization']['parentLocalization'] = $parent->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();
        $this->assertContains('Localization has been saved', $html);

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     * @return int
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_locale_localization_view', ['id' => $id]));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Localization $parent */
        $parent = $this->getReference(self::PARENT_LOCALIZATION);

        $html = $crawler->html();
        $this->assertContains(self::UPDATED_NAME, $html);
        $this->assertContains(self::UPDATED_DEFAULT_TITLE, $html);
        $this->assertContains($this->getLanguageFormatter()->format(self::UPDATED_LANGUAGE_CODE), $html);
        $this->assertContains($this->getFormattingFormatter()->format(self::UPDATED_FORMATTING_CODE), $html);
        $this->assertContains($parent->getName(), $html);

        return $id;
    }

    /**
     * @depends testView
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'DELETE',
                    'entityId' => $id,
                    'entityClass' => $this->getContainer()->getParameter('oro_locale.entity.localization.class')
                ]
            ),
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('oro_locale_localization_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->client->request('GET', $this->getUrl('oro_locale_localization_view', ['id' => $id]));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    /**
     * @return LanguageCodeFormatter
     */
    protected function getLanguageFormatter()
    {
        return $this->getContainer()->get('oro_locale.formatter.language_code');
    }

    /**
     * @return FormattingCodeFormatter
     */
    protected function getFormattingFormatter()
    {
        return $this->getContainer()->get('oro_locale.formatter.formatting_code');
    }

    /**
     * @param string|null $name
     * @return Localization
     */
    private function getLocalization($name = null)
    {
        $localization = $this->getRepository()->findOneBy($name ? ['name' => $name] : []);

        if (!$localization) {
            throw new \LogicException('At least one localization must be defined');
        }

        return $localization;
    }

    /**
     * @return LocalizationRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroLocaleBundle:Localization')
            ->getRepository('OroLocaleBundle:Localization');
    }
}
