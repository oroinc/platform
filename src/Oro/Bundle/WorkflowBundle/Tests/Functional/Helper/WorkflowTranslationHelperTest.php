<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Helper;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowTranslations;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowTranslationHelperTest extends WebTestCase
{
    /** @var WorkflowTranslationHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowTranslations::class, LoadWorkflowDefinitions::class]);

        $this->helper = $this->getContainer()->get('oro_workflow.helper.translation');
    }

    /**
     * @param string $workflowName
     * @param string $locale
     * @param array $translations
     *
     * @dataProvider findWorkflowTranslationsProvider
     */
    public function testFindWorkflowTranslations($workflowName, $locale, array $translations)
    {
        $this->assertEquals($translations, $this->helper->findWorkflowTranslations($workflowName, $locale));
    }

    /**
     * @return array
     */
    public function findWorkflowTranslationsProvider()
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
     * @param string $key
     * @param string $workflowName
     * @param string $locale
     * @param string $translation
     *
     * @dataProvider findWorkflowTranslationProvider
     */
    public function testFindWorkflowTranslation($key, $workflowName, $locale, $translation)
    {
        $this->assertEquals($translation, $this->helper->findWorkflowTranslation($key, $workflowName, $locale));
    }

    /**
     * @return array
     */
    public function findWorkflowTranslationProvider()
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
     * @param string $key
     * @param string $locale
     * @param string $translation
     *
     * @dataProvider findTranslationProvider
     */
    public function testFindTranslation($key, $locale, $translation)
    {
        $this->assertEquals($translation, $this->helper->findTranslation($key, $locale));
    }

    /**
     * @return array
     */
    public function findTranslationProvider()
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
     * @param array $expected
     * @param array $keys
     * @param string|null $default
     *
     * @dataProvider translationsDataProvider
     */
    public function testGenerateDefinitionTranslations(array $expected, array $keys, $default)
    {
        $this->assertSame($expected, $this->helper->generateDefinitionTranslations($keys, 'cn', $default));
    }

    /**
     * @return \Generator
     */
    public function translationsDataProvider()
    {
        yield [
                'expected' => ['oro.workflow.test_flow.label' => 'oro.workflow.test_flow.label'],
                'keys' => ['oro.workflow.test_flow.label'],
                'default' => 'oro.workflow.test_flow.label',
        ];
        yield [
                'expected' => ['oro.workflow.test_flow.label' => null],
                'keys' => ['oro.workflow.test_flow.label'],
                'default' => null,
        ];
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        return $this->getContainer()->get('translator');
    }
}
