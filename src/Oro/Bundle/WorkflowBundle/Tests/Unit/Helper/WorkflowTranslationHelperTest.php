<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class WorkflowTranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var TranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $translationHelper;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var WorkflowTranslationHelper */
    private $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $this->manager = $this->getMockBuilder(TranslationManager::class)->disableOriginalConstructor()->getMock();

        $this->translationHelper = $this->getMockBuilder(TranslationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new WorkflowTranslationHelper($this->translator, $this->manager, $this->translationHelper);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->manager, $this->helper, $this->translationHelper);
    }

    public function testSaveTranslationNoTranslationForDefaultLocale()
    {
        $locale = 'pl';
        $domain = WorkflowTranslationHelper::TRANSLATION_DOMAIN;
        $key = 'test_key';
        $value = 'test_value';

        $this->assertNotEquals($locale, Translation::DEFAULT_LOCALE, 'Test custom locale should be changed from pl');

        $this->translator->expects($this->any())->method('getLocale')->willReturn($locale);

        $this->manager->expects($this->at(0))
            ->method('saveValue')
            ->with($key, $value, $locale, $domain, Translation::SCOPE_UI);
        $this->manager->expects($this->at(1))
            ->method('saveValue')
            ->with($key, $value, Translation::DEFAULT_LOCALE, $domain, Translation::SCOPE_UI);

        $this->translationHelper->expects($this->once())
            ->method('findValue')
            ->with($key, Translation::DEFAULT_LOCALE, $domain)
            ->willReturn($key); //case when it returns key itself (so no value in db)

        $this->helper->saveTranslation($key, $value);
    }

    public function testSaveTranslationWithTranslationForDefaultLocale()
    {
        $locale = 'pl';
        $domain = WorkflowTranslationHelper::TRANSLATION_DOMAIN;
        $key = 'test_key';
        $value = 'test_value';

        $this->translator->expects($this->any())->method('getLocale')->willReturn($locale);

        $this->manager->expects($this->once())
            ->method('saveValue')
            ->with($key, $value, $locale, $domain, Translation::SCOPE_UI);

        //case when it returns a value would not save in case of real translation value already exists in db
        $this->translationHelper->expects($this->once())
            ->method('findValue')
            ->with($key, Translation::DEFAULT_LOCALE, $domain)
            ->willReturn('some translation string');

        $this->helper->saveTranslation($key, $value);
    }

    public function testSaveTranslationForDefaultLocale()
    {
        $locale = Translation::DEFAULT_LOCALE;
        $domain = WorkflowTranslationHelper::TRANSLATION_DOMAIN;
        $key = 'test_key';
        $value = 'test_value';

        $this->translator->expects($this->once())->method('getLocale')->willReturn($locale);

        $this->manager->expects($this->once())
            ->method('saveValue')
            ->with($key, $value, $locale, $domain, Translation::SCOPE_UI);

        $this->translationHelper->expects($this->never())->method('findValue');

        $this->helper->saveTranslation($key, $value);
    }

    public function testEnsureTranslationKey()
    {
        $key = 'key_to_be_sure_that_exists';
        $this->manager->expects($this->once())->method('findTranslationKey')->with($key, 'workflows');
        $this->helper->ensureTranslationKey($key);
    }

    public function testRemoveTranslationKey()
    {
        $key = 'key_to_remove';
        $this->manager->expects($this->once())->method('removeTranslationKey')->with($key, 'workflows');
        $this->helper->removeTranslationKey($key);
    }

    public function testPrepareTranslations()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('locale1');

        $this->translationHelper->expects($this->once())
            ->method('prepareValues')
            ->with('oro.workflow.workflow1', 'locale1', WorkflowTranslationHelper::TRANSLATION_DOMAIN);

        $this->helper->prepareTranslations('workflow1');
    }

    public function testFindTranslation()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('locale1');

        $this->translationHelper->expects($this->once())
            ->method('findValue')
            ->with('test-key', 'locale1', WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturn('value');

        $this->assertEquals('value', $this->helper->findTranslation('test-key'));
    }

    public function testGetTranslation()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('locale1');

        $this->translationHelper->expects($this->once())
            ->method('getValue')
            ->with('test-key', 'locale1', WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturn('value');

        $this->assertEquals('value', $this->helper->getTranslation('test-key'));
    }

    public function testExtractTranslations()
    {
        $definition = (new WorkflowDefinition())
            ->setName('definition1')
            ->setLabel('definition1.label')
            ->setSteps([
                (new WorkflowStep())->setName('step2')->setLabel('step2.label'),
            ])
            ->setConfiguration([
                'steps' => [
                    'step2' => ['label' => 'step2.label'],
                ],
                'transitions' => [
                    'transition1' => [
                        'label' => 'transition1.label',
                        'message' => 'transition1.message',
                    ]
                ],
                'attributes' => [
                    'attribute1' => ['label' => 'attribute1.label'],
                ],
            ]);

        $expectedDefinition = (new WorkflowDefinition())
            ->setName('definition1')
            ->setLabel('definition1.label-locale1.workflows')
            ->setSteps([
                (new WorkflowStep())->setName('step2')->setLabel('step2.label-locale1.workflows'),
            ])
            ->setConfiguration([
                'steps' => [
                    'step2' => ['label' => 'step2.label-locale1.workflows'],
                ],
                'transitions' => [
                    'transition1' => [
                        'label' => 'transition1.label-locale1.workflows',
                        'message' => 'transition1.message-locale1.workflows',
                    ]
                ],
                'attributes' => [
                    'attribute1' => ['label' => 'attribute1.label-locale1.workflows'],
                ]
            ]);

        $this->translator->expects($this->any())->method('getLocale')->willReturn('locale1');
        $this->translationHelper->expects($this->once())->method('prepareValues');

        // label + iterated keys
        // @todo: update in BAP-12019
        $this->translationHelper->expects($this->exactly(5))
            ->method('getValue')
            ->will($this->returnCallback(function ($key, $locale, $domain) {
                return sprintf('%s-%s.%s', $key, $locale, $domain);
            }));

        $this->helper->extractTranslations($definition);

        $this->assertEquals($expectedDefinition, $definition);
    }

    public function testExtractTranslationsWithWorkflowName()
    {
        $definition = (new WorkflowDefinition())
            ->setName('definition1')
            ->setConfiguration(['steps' => [], 'transitions' => [], 'attributes' => []]);

        $this->helper->extractTranslations($definition, 'customName');
    }

    /**
     * @param string $workflowName
     * @return TranslationKeySource
     */
    protected function getWorkflowSource($workflowName)
    {
        $translationKeySource = new TranslationKeySource(
            new WorkflowTemplate(),
            ['workflow_name' => $workflowName]
        );

        return $translationKeySource;
    }
}
