<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Symfony\Component\OptionsResolver\Options;

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
            ->setDefaults(
                [
                    'entity_class' => function (Options $options, $value) use ($context) {
                        if (null === $value && $context->data()->has('entity')) {
                            $entity = $context->data()->get('entity');
                            if (is_object($entity)) {
                                $value = ClassUtils::getClass($entity);
                            }
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes(['entity_class' => ['string', 'null']]);
    }
}
