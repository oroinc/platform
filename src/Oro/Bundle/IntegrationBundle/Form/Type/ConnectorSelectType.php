<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorTypeInterface;

class ConnectorSelectType extends AbstractSelectType
{
    const NAME = 'oro_integration_connector_select_form';

    /**
     * {@inheritdoc}
     */
    protected function getTypesArray($options)
    {
        // skip already used types
        $alreadyUsed = $options['already_used'];
        $types       = $this->registry->getRegisteredConnectorsTypes($options[self::TYPE_OPTION])
            ->partition(
                function ($type, ConnectorTypeInterface $connector) use ($alreadyUsed) {
                    return !in_array($type, $alreadyUsed);
                }
            );

        return reset($types);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setRequired([self::TYPE_OPTION, 'already_used']);
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
