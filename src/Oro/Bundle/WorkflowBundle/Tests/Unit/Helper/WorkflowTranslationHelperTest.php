<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowTranslationFieldsIterator;

class WorkflowTranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    const NODE = 'test_node';
    const ATTRIBUTE_NAME = 'test_attr_name';

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var TranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $translationHelper;

    /** @var TranslationKeyGenerator|\PHPUnit_Framework_MockObject_MockObject */
    private $keyGenerator;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var WorkflowTranslationFieldsIterator|\PHPUnit_Framework_MockObject_MockObject */
    private $fieldsIterator;

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

        $this->keyGenerator = $this->getMockBuilder(TranslationKeyGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldsIterator = $this->getMockBuilder(WorkflowTranslationFieldsIterator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new WorkflowTranslationHelper(
            $this->translator,
            $this->manager,
            $this->translationHelper,
            $this->keyGenerator,
            $this->fieldsIterator
        );
    }

    protected function tearDown()
    {
        unset($this->translator, $this->manager, $this->helper, $this->translationHelper, $this->keyGenerator);
    }

    public function testSaveTranslation()
    {
        $this->translator->expects($this->exactly(2))->method('getLocale')->willReturn('en');
        $this->manager
            ->expects($this->exactly(2))
            ->method('saveValue')
            ->with('test_key', 'test_value', 'en', WorkflowTranslationHelper::TRANSLATION_DOMAIN);
        $this->helper->saveTranslation('test_key', 'test_value');
        $this->helper->saveTranslation('test_key', 'test_value');
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
        $this->keyGenerator->expects($this->once())
            ->method('generate')
            ->with($this->getWorkflowSource('workflow1'))
            ->willReturn('generated-key');

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('locale1');

        $this->translationHelper->expects($this->once())
            ->method('prepareValues')
            ->with('generated-key', 'locale1', WorkflowTranslationHelper::TRANSLATION_DOMAIN);

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
                (new WorkflowStep())->setLabel('step2.label'),
            ])
            ->setConfiguration([
                'steps' => [
                    'step1' => ['label' => 'step1.label'],
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
                (new WorkflowStep())->setLabel('step2.label'),
            ])
            ->setConfiguration($definition->getConfiguration());

        $translationKeySource = $this->getWorkflowSource('workflow1');
        $this->keyGenerator->expects($this->once())->method('generate')->with($translationKeySource);
        $this->translator->expects($this->any())->method('getLocale')->willReturn('locale1');
        $this->translationHelper->expects($this->once())->method('prepareValues');
        $iteratedKeys = ['key1' => 'val1', 'key2' => 'val2'];
        // label + iterated keys
        $this->translationHelper->expects($this->exactly(count($iteratedKeys) + 1))
            ->method('getValue')
            ->will($this->returnCallback(function ($key, $locale, $domain) {
                return sprintf('%s-%s.%s', $key, $locale, $domain);
            }));

        $this->fieldsIterator
            ->expects($this->once())
            ->method('iterateConfigTranslationFields')
            ->willReturn($iteratedKeys);

        $this->helper->extractTranslations($definition, 'workflow1');

        $this->assertEquals($expectedDefinition, $definition);
    }

    public function testExtractTranslationsWithWorkflowName()
    {
        $definition = (new WorkflowDefinition())
            ->setName('definition1')
            ->setConfiguration(['steps' => [], 'transitions' => [], 'attributes' => []]);

        $translationKeySource = $this->getWorkflowSource('customName');

        $this->keyGenerator->expects($this->once())->method('generate')->with($translationKeySource);

        $this->fieldsIterator->expects($this->once())->method('iterateConfigTranslationFields')
            ->willReturn([]);
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
