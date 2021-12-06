<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Helper;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowTranslations;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowTranslationHelperTest extends WebTestCase
{
    /** @var WorkflowTranslationHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowTranslations::class, LoadWorkflowDefinitions::class]);

        $this->helper = $this->getContainer()->get('oro_workflow.helper.translation');
    }

    /**
     * @dataProvider findWorkflowTranslationsProvider
     */
    public function testFindWorkflowTranslations(string $workflowName, ?string $locale, array $translations)
    {
        $this->assertEquals($translations, $this->helper->findWorkflowTranslations($workflowName, $locale));
    }

    public function findWorkflowTranslationsProvider(): array
    {
        return [
            'workflow1' => [
                'workflow' => LoadWorkflowTranslations::WORKFLOW1,
                'locale' => LoadLanguages::LANGUAGE2,
                'translations' => [
                    'oro.workflow.workflow1.translation1.key' => 'translation1-1.value',
                    'oro.workflow.workflow1.translation2.key' => 'translation1-2.value',
                ],
            ],
            'workflow2' => [
                'workflow' => LoadWorkflowTranslations::WORKFLOW2,
                'locale' => LoadLanguages::LANGUAGE2,
                'translations' => [
                    'oro.workflow.workflow2.translation1.key' => 'translation2-1.value',
                ],
            ],
            'workflow3' => [
                'workflow' => LoadWorkflowTranslations::WORKFLOW3,
                'locale' => LoadLanguages::LANGUAGE2,
                'translations' => [],
            ],
        ];
    }

    /**
     * @dataProvider findWorkflowTranslationProvider
     */
    public function testFindWorkflowTranslation(
        string $key,
        string $workflowName,
        ?string $locale,
        string $translation
    ) {
        $this->assertEquals($translation, $this->helper->findWorkflowTranslation($key, $workflowName, $locale));
    }

    public function findWorkflowTranslationProvider(): array
    {
        return [
            'workflow1 unknown_key' => [
                'key' => 'oro.workflow.workflow1.translation1.unknown_key',
                'workflow' => 'workflow1',
                'locale' => LoadLanguages::LANGUAGE2,
                'translation' => 'oro.workflow.workflow1.translation1.unknown_key',
            ],
            'workflow1.key1.language2' => [
                'key' => 'oro.workflow.workflow1.translation1.key',
                'workflow' => 'workflow1',
                'locale' => LoadLanguages::LANGUAGE2,
                'translation' => 'translation1-1.value',
            ],
            'workflow1.key1.fallback (unknown locale)' => [
                'key' => 'oro.workflow.workflow1.translation1.key',
                'workflow' => 'workflow1',
                'locale' => 'unknown_locale',
                'translation' => 'translation1-1.default',
            ],
            'workflow1.key1.fallback (without locale)' => [
                'key' => 'oro.workflow.workflow1.translation1.key',
                'workflow' => 'workflow1',
                'locale' => null,
                'translation' => 'translation1-1.default',
            ],
        ];
    }

    /**
     * @dataProvider findTranslationProvider
     */
    public function testFindTranslation(string $key, string $locale, string $translation)
    {
        $this->assertEquals($translation, $this->helper->findTranslation($key, $locale));
    }

    public function findTranslationProvider(): array
    {
        return [
            'unknown key' => [
                'key' => 'unknown_key',
                'locale' => LoadLanguages::LANGUAGE2,
                'translation' => 'unknown_key',
            ],
            'key1.language2' => [
                'key' => LoadWorkflowTranslations::TRANSLATION1,
                'locale' => LoadLanguages::LANGUAGE2,
                'translation' => 'translation1.value',
            ],
            'key1.fallback' => [
                'key' => LoadWorkflowTranslations::TRANSLATION1,
                'locale' => 'unknown_language',
                'translation' => 'translation1.default',
            ],
        ];
    }

    public function testSaveTranslation()
    {
        $this->getTranslator()->setLocale(LoadLanguages::LANGUAGE2);

        $this->helper->saveTranslation('test.key', 'test.value');
        $this->helper->flushTranslations();

        $this->assertEquals('test.value', $this->helper->findTranslation('test.key', LoadLanguages::LANGUAGE2));
        $this->assertEquals('test.value', $this->helper->findTranslation('test.key', Translator::DEFAULT_LOCALE));
    }

    public function testSaveTranslationWithDefaultLocale()
    {
        $this->getTranslator()->setLocale(Translator::DEFAULT_LOCALE);

        $this->helper->saveTranslation('test.key', 'test.value');
        $this->helper->flushTranslations();

        $this->assertEquals('test.value', $this->helper->findTranslation('test.key', Translator::DEFAULT_LOCALE));
    }

    public function testSaveTranslationWithExistingDefaultTranslation()
    {
        $key = LoadWorkflowTranslations::TRANSLATION1;

        $this->getTranslator()->setLocale(LoadLanguages::LANGUAGE2);

        $this->helper->saveTranslation($key, 'changed value');
        $this->helper->flushTranslations();

        $this->assertEquals('translation1.default', $this->helper->findTranslation($key, Translator::DEFAULT_LOCALE));
        $this->assertEquals('changed value', $this->helper->findTranslation($key, LoadLanguages::LANGUAGE2));
    }

    public function testGenerateDefinitionTranslationKeys()
    {
        $expected = [
            'oro.workflow.test_flow.label',
            'oro.workflow.test_flow.step.open.label',
            'oro.workflow.test_flow.transition.start_transition.label',
            'oro.workflow.test_flow.transition.start_transition.button_label',
            'oro.workflow.test_flow.transition.start_transition.button_title',
            'oro.workflow.test_flow.transition.start_transition.warning_message',
        ];
        $workflowDefinition = $this->getReference('workflow.' . LoadWorkflowDefinitions::NO_START_STEP);
        $this->assertEquals($expected, $this->helper->generateDefinitionTranslationKeys($workflowDefinition));
    }

    /**
     * @dataProvider translationsDataProvider
     */
    public function testGenerateDefinitionTranslations(array $expected, array $keys, ?string $default)
    {
        $this->assertSame($expected, $this->helper->generateDefinitionTranslations($keys, 'cn', $default));
    }

    public function translationsDataProvider(): array
    {
        return [
            [
                'expected' => ['oro.workflow.test_flow.label' => 'oro.workflow.test_flow.label'],
                'keys' => ['oro.workflow.test_flow.label'],
                'default' => 'oro.workflow.test_flow.label',
            ],
            [
                'expected' => ['oro.workflow.test_flow.label' => null],
                'keys' => ['oro.workflow.test_flow.label'],
                'default' => null,
            ]
        ];
    }

    private function getTranslator(): LocaleAwareInterface
    {
        return $this->getContainer()->get('translator');
    }
}
