<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\DynamicTranslationKeySource;
use Oro\Bundle\WorkflowBundle\Translation\TranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\WorkflowBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Translation\TranslationKeyTemplateInterface;

class TranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    const NODE = 'test_node';
    const ATTRIBUTE_NAME = 'test_attr_name';

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var TranslationKeyGenerator|\PHPUnit_Framework_MockObject_MockObject */
    private $generator;

    /** @var TranslationHelper */
    private $helper;

    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $this->manager = $this->getMockBuilder(TranslationManager::class)->disableOriginalConstructor()->getMock();
        $this->generator = $this->getMock(TranslationKeyGenerator::class);
        $this->helper = new TranslationHelper($this->translator, $this->manager, $this->generator);
    }

    /**
     * @dataProvider updateNodeKeysDataProvider
     *
     * @param string $expected
     * @param array $config
     * @param bool $withTranslation
     */
    public function testUpdateNodeKeys($expected, array $config, $withTranslation = false)
    {
        /** @var TranslationKeyTemplateInterface|\PHPUnit_Framework_MockObject_MockObject $template */
        $template = $this->getMock(TranslationKeyTemplateInterface::class);
        $definition = new WorkflowDefinition();
        $definition->setConfiguration($config);

        if (0 !== count($config)) {
            if ($withTranslation) {
                $this->manager->expects($this->once())->method('saveValue');
            } else {
                $this->manager->expects($this->once())->method('findTranslationKey');
            }
        }

        $this->generator->expects($this->any())->method('generate')->willReturnCallback(
            function (TranslationKeySourceInterface $keySource) {
                $data = $keySource->getData();

                return sprintf('test.%s.test', $data[self::ATTRIBUTE_NAME]);
            }
        );

        $template->expects($this->any())->method('getRequiredKeys')->willReturn([self::ATTRIBUTE_NAME]);

        $this->helper->updateNodeKeys($template, self::NODE, self::ATTRIBUTE_NAME, 'label', $definition);

        $this->assertEquals($expected, $definition->getConfiguration());
    }

    /**
     * @return array
     */
    public function updateNodeKeysDataProvider()
    {
        return [
            'empty config' => [
                'expected' => [
                    self::NODE => [],
                ],
                'config' => [],
            ],
            'no label' => [
                'expected' => [
                    self::NODE => [
                        'item1' => ['label' => 'test.item1.test'],
                    ],
                ],
                'config' => [
                    self::NODE => [
                        'item1' => [],
                    ],
                ],
            ],
            'empty label' => [
                'expected' => [
                    self::NODE => [
                        'item1' => ['label' => 'test.item1.test'],
                    ],
                ],
                'config' => [
                    self::NODE => [
                        'item1' => ['label' => ''],
                    ],
                ],
                'withTranslation' => true,
            ],
            'with label' => [
                'expected' => [
                    self::NODE => [
                        'item1' => ['label' => 'test.item1.test'],
                    ],
                ],
                'config' => [
                    self::NODE => [
                        'item1' => ['label' => 'test label'],
                    ],
                ],
                'withTranslation' => true,
            ],
        ];
    }

    /**
     * @dataProvider cleanupNodeKeysDataProvider
     *
     * @param array $config
     * @param array $oldConfig
     */
    public function testCleanupNodeKeys(array $config, array $oldConfig)
    {
        /** @var TranslationKeyTemplateInterface|\PHPUnit_Framework_MockObject_MockObject $template */
        $template = $this->getMock(TranslationKeyTemplateInterface::class);
        $definition = new WorkflowDefinition();
        $definition->setConfiguration($config);

        if (0 !== count($oldConfig)) {
            $prevDefinition = new WorkflowDefinition();
            $prevDefinition->setConfiguration($oldConfig);
            $this->manager->expects($this->once())->method('removeTranslationKey');
            $this->generator->expects($this->once())->method('generate');
            $template->expects($this->once())->method('getRequiredKeys')->willReturn([self::ATTRIBUTE_NAME]);
        } else {
            $this->generator->expects($this->never())->method('generate');
            $template->expects($this->never())->method('getRequiredKeys');
            $prevDefinition = null;
        }

        $this->helper->cleanupNodeKeys($template, self::NODE, self::ATTRIBUTE_NAME, $definition, $prevDefinition);
    }

    /**
     * @return array
     */
    public function cleanupNodeKeysDataProvider()
    {
        return [
            'empty old config' => [
                'config' => [
                    self::NODE => [
                        'item1' => ['label' => 'test label'],
                    ],
                ],
                'oldConfig' => [],
            ],
            'filled old config' => [
                'config' => [
                    self::NODE => [
                        'item1' => ['label' => 'test label'],
                    ],
                ],
                'oldConfig' => [
                    self::NODE => [
                        'item2' => ['label' => 'test label 2'],
                    ],
                ],
            ],
        ];
    }

    public function testSaveTranslation()
    {
        // current locale retrieve only once
        $this->translator->expects($this->once())->method('getLocale')->willReturn('en');
        $this->manager
            ->expects($this->exactly(2))
            ->method('saveValue')
            ->with('test_key', 'test_value', 'en', TranslationHelper::WORKFLOWS_DOMAIN);
        $this->helper->saveTranslation('test_key', 'test_value');
        $this->helper->saveTranslation('test_key', 'test_value');
    }


    public function testGenerateKey()
    {
        /** @var TranslationKeyTemplateInterface|\PHPUnit_Framework_MockObject_MockObject $template */
        $template = $this->getMock(TranslationKeyTemplateInterface::class);
        /** @var DynamicTranslationKeySource|\PHPUnit_Framework_MockObject_MockObject $keySource */
        $keySource = $this->getMock(DynamicTranslationKeySource::class);
        $keySource->expects($this->once())->method('configure')->with($template, []);
        $this->generator->expects($this->once())->method('generate')->with($keySource);

        $this->helper->generateKey($keySource, $template);
    }
}
