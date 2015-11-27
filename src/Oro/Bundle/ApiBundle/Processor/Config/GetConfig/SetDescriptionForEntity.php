<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class SetDescriptionForEntity implements ProcessorInterface
{
    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param EntityClassNameProviderInterface $entityClassNameProvider
     * @param ConfigProvider                   $entityConfigProvider
     */
    public function __construct(
        EntityClassNameProviderInterface $entityClassNameProvider,
        ConfigProvider $entityConfigProvider
    ) {
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->entityConfigProvider    = $entityConfigProvider;
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
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        if (!isset($definition[ConfigUtil::LABEL])) {
            $entityName = $this->entityClassNameProvider->getEntityClassName($entityClass);
            if ($entityName) {
                $definition[ConfigUtil::LABEL] = $entityName;
            }
        }
        if (!isset($definition[ConfigUtil::PLURAL_LABEL])) {
            $entityPluralName = $this->entityClassNameProvider->getEntityClassPluralName($entityClass);
            if ($entityPluralName) {
                $definition[ConfigUtil::PLURAL_LABEL] = $entityPluralName;
            }
        }
        if (!isset($definition[ConfigUtil::DESCRIPTION]) && $this->entityConfigProvider->hasConfig($entityClass)) {
            $definition[ConfigUtil::DESCRIPTION] = new Label(
                $this->entityConfigProvider->getConfig($entityClass)->get('description')
            );
        }

        $context->setResult($definition);
    }
}
