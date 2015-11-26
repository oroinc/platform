<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class SetDescriptionForEntity implements ProcessorInterface
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(ConfigProvider $entityConfigProvider)
    {
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (empty($definition)) {
            // an entity configuration does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass || !$this->entityConfigProvider->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }

        $entityConfig = $this->entityConfigProvider->getConfig($entityClass);

        if (!isset($definition[ConfigUtil::LABEL])) {
            $definition[ConfigUtil::LABEL] = new Label($entityConfig->get('label'));
        }
        if (!isset($definition[ConfigUtil::PLURAL_LABEL])) {
            $definition[ConfigUtil::PLURAL_LABEL] = new Label($entityConfig->get('plural_label'));
        }
        if (!isset($definition[ConfigUtil::DESCRIPTION])) {
            $definition[ConfigUtil::DESCRIPTION] = new Label($entityConfig->get('description'));
        }

        $context->setResult($definition);
    }
}
