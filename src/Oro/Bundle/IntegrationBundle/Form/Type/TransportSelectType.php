<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

class TransportSelectType extends AbstractSelectType
{
    const NAME = 'oro_integration_transport_select_form';

    /**
     * {@inheritdoc}
     */
    protected function getTypesArray($options)
    {
        return $this->registry->getRegisteredTransportTypes($options[self::TYPE_OPTION]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
