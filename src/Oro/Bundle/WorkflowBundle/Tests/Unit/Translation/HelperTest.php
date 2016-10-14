<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Translation\Helper;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var TranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationHelper;

    /** @var TranslationKeyGenerator|\PHPUnit_Framework_MockObject_MockObject */
    protected $keyGenerator;

    /** @var Helper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);

        $this->translationHelper = $this->getMockBuilder(TranslationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->keyGenerator = $this->getMockBuilder(TranslationKeyGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new Helper($this->translator, $this->translationHelper, $this->keyGenerator);
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
            ->with('generated-key', 'locale1', Helper::TRANSLATION_DOMAIN);

        $this->helper->prepareTranslations('workflow1');
    }

    public function testFindTranslation()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('locale1');

        $this->translationHelper->expects($this->once())
            ->method('findValue')
            ->with('test-key', 'locale1', Helper::TRANSLATION_DOMAIN)
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
            ->with('test-key', 'locale1', Helper::TRANSLATION_DOMAIN)
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
                (new WorkflowStep())->setLabel('step2.label-locale1.workflows'),
            ])
            ->setConfiguration([
                'steps' => [
                    'step1' => ['label' => 'step1.label-locale1.workflows']
                ],
                'transitions' => [
                    'transition1' => [
                        'label' => 'transition1.label-locale1.workflows',
                        'message' => 'transition1.message-locale1.workflows',
                    ]
                ],
                'attributes' => [
                    'attribute1' => ['label' => 'attribute1.label-locale1.workflows']
                ],
            ]);

        $translationKeySource = $this->getWorkflowSource('workflow1');

        $this->keyGenerator->expects($this->once())->method('generate')->with($translationKeySource);
        $this->translator->expects($this->any())->method('getLocale')->willReturn('locale1');
        $this->translationHelper->expects($this->once())->method('prepareValues');

        $this->translationHelper->expects($this->exactly(6))
            ->method('getValue')
            ->will($this->returnCallback(function ($key, $locale, $domain) {
                return sprintf('%s-%s.%s', $key, $locale, $domain);
            }));

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
