<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Oro\Bundle\TranslationBundle\Translation\KeySource\DynamicTranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\CallBackTranslationGenerator;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\ConfigResource;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\GeneratedTranslationResource;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\MovementOptions;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\TranslationsExtractor;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\Workflow\WorkflowsUtil;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DefinitionUpgrade20Command extends ContainerAwareCommand
{
    const NAME = 'oro:workflow:definitions:upgrade20';

    protected function configure()
    {
        $this->setName(self::NAME);
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = new MovementOptions();
        $options->setBundles($this->getContainer()->get('kernel')->getBundles());
        $options->setConfigFilePath('Resources/config/oro/workflows.yml');
        $options->setTranslationFilePath('Resources/translations/workflows.en.yml');

        $extractor = new TranslationsExtractor($options);

        //common generator
        $transKeyGenerator = new TranslationKeyGenerator();
        //common dynamic source
        $keySource = new DynamicTranslationKeySource();

        //workflow labels
        $workflowLabelTemplate = new WorkflowLabelTemplate();
        $extractor->addResourceTranslationGenerator(
            new CallBackTranslationGenerator(
                function (ConfigResource $configResource) use ($transKeyGenerator, $keySource, $workflowLabelTemplate) {
                    $data = $configResource->getData();
                    foreach (WorkflowsUtil::workflows($data) as $workflowName => $workflowConfig) {
                        if (array_key_exists('label', $workflowConfig)) {
                            $key = $transKeyGenerator->generate(
                                $keySource->configure($workflowLabelTemplate, ['workflow_name' => $workflowName])
                            );
                            yield new GeneratedTranslationResource(
                                ['workflows', $workflowName, 'label'],
                                $key,
                                $workflowConfig['label']
                            );
                        }
                    }
                }
            )
        );

        //steps labels
        $stepLabelTemplate = new StepLabelTemplate();
        $extractor->addResourceTranslationGenerator(new CallBackTranslationGenerator(
            function (ConfigResource $configResource) use ($transKeyGenerator, $keySource, $stepLabelTemplate) {
                $data = $configResource->getData();
                foreach (WorkflowsUtil::workflows($data) as $workflowName => $workflowConfig) {
                    foreach (WorkflowsUtil::steps($workflowConfig) as $stepName => $stepConfig) {
                        if (array_key_exists('label', $stepConfig)) {
                            $key = $transKeyGenerator->generate(
                                $keySource->configure(
                                    $stepLabelTemplate,
                                    [
                                        'workflow_name' => $workflowName,
                                        'step_name' => $stepName
                                    ]
                                )
                            );
                            yield new GeneratedTranslationResource(
                                ['workflows', $workflowName, WorkflowConfiguration::NODE_STEPS, $stepName, 'label'],
                                $key,
                                $stepConfig['label']
                            );
                        }
                    }
                }
            }
        ));

        //attributes labels
        $attributeLabelTemplate = new WorkflowAttributeLabelTemplate();
        $extractor->addResourceTranslationGenerator(
            new CallBackTranslationGenerator(
                function (ConfigResource $configResource) use (
                    $transKeyGenerator,
                    $keySource,
                    $attributeLabelTemplate
                ) {
                    $data = $configResource->getData();
                    foreach (WorkflowsUtil::workflows($data) as $workflowName => $wfc) {
                        foreach (WorkflowsUtil::attributes($wfc) as $attributeName => $attributeConfig) {
                            if (array_key_exists('label', $attributeConfig)) {
                                $key = $transKeyGenerator->generate(
                                    $keySource->configure(
                                        $attributeLabelTemplate,
                                        [
                                            'workflow_name' => $workflowName,
                                            'attribute_name' => $attributeName
                                        ]
                                    )
                                );

                                yield new GeneratedTranslationResource(
                                    [
                                        'workflows',
                                        $workflowName,
                                        WorkflowConfiguration::NODE_ATTRIBUTES,
                                        $attributeName,
                                        'label'
                                    ],
                                    $key,
                                    $attributeConfig['label']
                                );
                            }
                        }
                    }
                }
            )
        );

        //transition labels and warning messages
        $transitionLabelTemplate = new TransitionLabelTemplate();
        $warningMessageTemplate = new TransitionWarningMessageTemplate();
        $transitionAttributeLabelTemplate = new TransitionAttributeLabelTemplate();
        $extractor->addResourceTranslationGenerator(
            new CallBackTranslationGenerator(
                function (ConfigResource $configResource) use (
                    $transKeyGenerator,
                    $keySource,
                    $transitionLabelTemplate,
                    $warningMessageTemplate,
                    $transitionAttributeLabelTemplate
                ) {
                    $data = $configResource->getData();
                    foreach (WorkflowsUtil::workflows($data) as $workflowName => $workflowConfig) {
                        foreach (WorkflowsUtil::transitions($workflowConfig) as $transitionName => $transitionConfig) {
                            $context = ['workflow_name' => $workflowName, 'transition_name' => $transitionName];
                            if (array_key_exists('label', $transitionConfig)) {
                                yield new GeneratedTranslationResource(
                                    ['workflows', $workflowName, 'transitions', $transitionName, 'label'],
                                    $transKeyGenerator->generate(
                                        $keySource->configure($transitionLabelTemplate, $context)
                                    ),
                                    $transitionConfig['label']
                                );
                            }
                            if (array_key_exists('message', $transitionConfig)) {
                                yield new GeneratedTranslationResource(
                                    ['workflows', $workflowName, 'transitions', $transitionName, 'message'],
                                    $transKeyGenerator->generate(
                                        $keySource->configure($warningMessageTemplate, $context)
                                    ),
                                    $transitionConfig['message']
                                );
                            }

                            if (isset($transitionConfig['form_options']['attribute_fields'])
                                && is_array($transitionConfig['form_options']['attribute_fields'])
                            ) {
                                foreach ($transitionConfig['form_options']['attribute_fields'] as $attr => &$attrCfg) {
                                    if (isset($attrCfg['options']['label'])) {
                                        $context['attribute_name'] = $attr;
                                        yield new GeneratedTranslationResource(
                                            [
                                                'workflows',
                                                $workflowName,
                                                'transitions',
                                                $transitionName,
                                                'form_options',
                                                'attribute_fields',
                                                $attr,
                                                'options',
                                                'label'
                                            ],
                                            $transKeyGenerator->generate(
                                                $keySource->configure($transitionAttributeLabelTemplate, $context)
                                            ),
                                            $attrCfg['options']['label']
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            )
        );

        //worklfow.yml files updation section below
        $emitLinesWithNodes = function ($content) {
            $current = '';
            foreach (explode("\n", $content) as $line) {
                $m = [];
                if (preg_match('/^[ ]{0,}(\w+)(?=:)/', $line, $m)) {
                    $current = $m[1];
                }
                yield $current => $line;
            }
        };

        $isEmptyLine = function ($line) {
            $isComment = function ($l) {
                $ltrimmedLine = ltrim($l, ' ');

                return '' !== $ltrimmedLine && $ltrimmedLine[0] === '#';
            };
            $isBlank = function ($l) {
                return '' == trim($l, ' ');
            };

            return $isBlank($line) || $isComment($line);
        };

        $extractor->setResourceUpdater(
            function (
                ConfigResource $configResource,
                GeneratedTranslationResource $translationResource
            ) use (
                $emitLinesWithNodes,
                $isEmptyLine
            ) {
                $content = $configResource->getContent();
                $path = $translationResource->getPath();
                $value = $translationResource->getValue();

                $newContent = '';
                $next = array_shift($path);
                $lastNode = null;
                $matchLines = [];
                foreach ($emitLinesWithNodes($content) as $current => $line) {
                    if ($lastNode) {
                        if ($isEmptyLine($line) || $lastNode === $current) {
                            $matchLines[] = $line;
                            continue;
                        } else {
                            $firstLine = reset($matchLines);
                            preg_match('/^[ ]+/', $firstLine, $m);

                            $cleaning = function ($line) use ($m) {
                                return preg_replace("/^{$m[0]}/", '', $line);
                            };

                            $parsed = Yaml::parse(implode("\n", array_map($cleaning, $matchLines)));

                            if (trim($parsed[$lastNode], "\n") !== trim($value, "\n")) {
                                throw new \LogicException(
                                    sprintf(
                                        'Cant find key %s in %s with value %s. Searching by path %s',
                                        $translationResource->getKey(),
                                        $configResource->getFile()->getRealPath(),
                                        $value,
                                        implode('.', $translationResource->getPath())
                                    )
                                );
                            }
                            $lastNode = null;
                            $matchLines = [];
                        }
                    }
                    if ($current === $next) {
                        $next = array_shift($path);
                        if ($next === null) {
                            $lastNode = $current;
                            $matchLines[] = $line;
                            continue;
                        }
                    }
                    $newContent .= $line . "\n";
                }

                $configResource->updateContent(preg_replace('/\n\n(?=\Z)/', "\n", $newContent));
            }
        );

        $inlineKeysInTranslationFile = false; //false means tree will be expanded
        $extractor->execute($inlineKeysInTranslationFile);
    }
}
