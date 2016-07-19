<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;

class ApplicableEntitiesType extends AbstractType
{
    const NAME = 'oro_workflow_applicable_entities';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var EntityConnector
     */
    protected $entityConnector;

    /**
     * @param ConfigManager $configManager
     * @param EntityConnector $entityConnector
     */
    public function __construct(ConfigManager $configManager, EntityConnector $entityConnector)
    {
        $this->configManager = $configManager;
        $this->entityConnector = $entityConnector;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return EntityChoiceType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['apply_exclusions' => false]);

        $resolver->setNormalizers(
            array(
                'choices' => function (Options $options, $choices) {
                    foreach ($choices as $class => $item) {
                        if (!$this->isClassApplicable($class)) {
                            unset($choices[$class]);
                        }
                    }

                    return $choices;
                }
            )
        );
    }

    /**
     * @param string $class
     * @return bool
     */
    protected function isClassApplicable($class)
    {
        if ($this->entityConnector->isWorkflowAware($class)) {
            return true;
        }

        $extendConfigProvider = $this->getExtendConfigProvider();
        if (!$extendConfigProvider->hasConfig($class)) {
            return false;
        }

        $entityConfig = $extendConfigProvider->getConfig($class);

        return $entityConfig && $entityConfig->is('is_extend');
    }

    /**
     * @return ConfigProvider
     */
    protected function getExtendConfigProvider()
    {
        if (!$this->extendConfigProvider) {
            $this->extendConfigProvider = $this->configManager->getProvider('extend');
        }

        return $this->extendConfigProvider;
    }
}
