<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Oro\Bundle\WorkflowBundle\Command\Upgrade20\CallBackTranslationGenerator;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\ConfigResource;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\GeneratedTranslationResource;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\MovementOptions;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\TranslationsExtractor;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\Workflow\WorkflowsUtil;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\Workflow\WorkflowTranslationTools;
use Oro\Bundle\WorkflowBundle\Command\Upgrade20\YamlContentUtils;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DefinitionUpgrade20Command extends ContainerAwareCommand
{
    const NAME = 'oro:workflow:definitions:upgrade20';

    /**
     * @var WorkflowTranslationTools
     */
    private $workflowTools;

    protected function configure()
    {
        $this->setName(self::NAME);

        $this->addOption('expand', 'x', InputOption::VALUE_OPTIONAL);
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = new MovementOptions();
        $options->setBundles($this->getContainer()->get('kernel')->getBundles());
        $options->setConfigFilePath('Resources/config/oro/workflows.yml');
        $options->setTranslationFilePath('Resources/translations/workflows.en.yml');

        $extractor = new TranslationsExtractor($options);

        $this->workflowTools = new WorkflowTranslationTools();

        $extractor->addResourceTranslationGenerator($this->getWorkflowLabelGenerator());
        $extractor->addResourceTranslationGenerator($this->getStepLabelGenerator());
        $extractor->addResourceTranslationGenerator($this->getAttributeLabelGenerator());
        $extractor->addResourceTranslationGenerator($this->getTransitionFieldsGenerator());

        $extractor->setResourceUpdater(YamlContentUtils::getCallableResourceUpdater());

        $extractor->execute(!$input->getOption('expand'));
    }

    /**
     * @return CallBackTranslationGenerator
     */
    protected function getWorkflowLabelGenerator()
    {
        return new CallBackTranslationGenerator(
            function (ConfigResource $configResource) {
                $data = $configResource->getData();
                foreach (WorkflowsUtil::workflows($data) as $workflowName => $workflowConfig) {
                    if (array_key_exists('label', $workflowConfig)) {
                        $key = $this->workflowTools->workflowLabelKey(['workflow_name' => $workflowName]);
                        yield new GeneratedTranslationResource(
                            ['workflows', $workflowName, 'label'],
                            $key,
                            $workflowConfig['label']
                        );
                    }
                }
            }
        );
    }

    /**
     * @return CallBackTranslationGenerator
     */
    protected function getStepLabelGenerator()
    {
        return new CallBackTranslationGenerator(
            function (ConfigResource $configResource) {
                $data = $configResource->getData();
                foreach (WorkflowsUtil::workflows($data) as $workflowName => $workflowConfig) {
                    foreach (WorkflowsUtil::steps($workflowConfig) as $stepName => $stepConfig) {
                        if (!is_array($stepConfig)) {
                            continue;
                        }
                        if (array_key_exists('label', $stepConfig)) {
                            $key = $this->workflowTools->stepLabelKey([
                                'workflow_name' => $workflowName,
                                'step_name' => $stepName
                            ]);
                            yield new GeneratedTranslationResource(
                                ['workflows', $workflowName, WorkflowConfiguration::NODE_STEPS, $stepName, 'label'],
                                $key,
                                $stepConfig['label']
                            );
                        }
                    }
                }
            }
        );
    }

    /**
     * @return CallBackTranslationGenerator
     */
    protected function getAttributeLabelGenerator()
    {
        return new CallBackTranslationGenerator(
            function (ConfigResource $configResource) {
                $data = $configResource->getData();
                foreach (WorkflowsUtil::workflows($data) as $workflowName => $wfc) {
                    foreach (WorkflowsUtil::attributes($wfc) as $attributeName => $attributeConfig) {
                        if (array_key_exists('label', $attributeConfig)) {
                            $key = $this->workflowTools->attributeLabelKey([
                                'workflow_name' => $workflowName,
                                'attribute_name' => $attributeName
                            ]);

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
        );
    }

    /**
     * @return CallBackTranslationGenerator
     */
    protected function getTransitionFieldsGenerator()
    {
        return new CallBackTranslationGenerator(
            function (ConfigResource $configResource) {
                $data = $configResource->getData();
                foreach (WorkflowsUtil::workflows($data) as $workflowName => $workflowConfig) {
                    foreach (WorkflowsUtil::transitions($workflowConfig) as $transitionName => $transitionConfig) {
                        $context = ['workflow_name' => $workflowName, 'transition_name' => $transitionName];
                        if (array_key_exists('label', $transitionConfig)) {
                            yield new GeneratedTranslationResource(
                                ['workflows', $workflowName, 'transitions', $transitionName, 'label'],
                                $this->workflowTools->transitionLabelKey($context),
                                $transitionConfig['label']
                            );
                        }
                        if (array_key_exists('message', $transitionConfig)) {
                            yield new GeneratedTranslationResource(
                                ['workflows', $workflowName, 'transitions', $transitionName, 'message'],
                                $this->workflowTools->transitionMessageKey($context),
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
                                        $this->workflowTools->transitionAttributeLabelKey($context),
                                        $attrCfg['options']['label']
                                    );
                                }
                            }
                        }
                    }
                }
            }
        );
    }
}
