<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Registers the 'entity_class' variable in the layout context and tries to get
 * its value based on the 'entity' data stored in the context data.
 */
class EntityClassContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setOptional(['entity_class'])
            ->setAllowedTypes(['entity_class' => ['string', 'null']]);

        if ($context->has('entity_class')) {
            return;
        }
        if (!$context->data()->has('entity')) {
            return;
        }

        $entity = $context->data()->get('entity');
        if (is_object($entity)) {
            $context->set('entity_class', ClassUtils::getClass($entity));
        }
    }
}
