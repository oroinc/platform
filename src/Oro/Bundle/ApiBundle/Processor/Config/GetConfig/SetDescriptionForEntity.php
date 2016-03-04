<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Adds "label", "plural_label" and "description" attributes for the entity.
 */
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
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $entityClass = $context->getClassName();
        if (!$definition->hasLabel()) {
            $entityName = $this->entityClassNameProvider->getEntityClassName($entityClass);
            if ($entityName) {
                $definition->setLabel($entityName);
            }
        }
        if (!$definition->hasPluralLabel()) {
            $entityPluralName = $this->entityClassNameProvider->getEntityClassPluralName($entityClass);
            if ($entityPluralName) {
                $definition->setPluralLabel($entityPluralName);
            }
        }
        if (!$definition->hasDescription() && $this->entityConfigProvider->hasConfig($entityClass)) {
            $definition->setDescription(
                new Label($this->entityConfigProvider->getConfig($entityClass)->get('description'))
            );
        }
    }
}
