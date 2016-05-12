<?php

namespace Oro\Bundle\EntityExtendBundle\Model\Action;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\Action\Action\RequestEntity as BaseRequestEntity;
use Oro\Component\Action\Exception\InvalidParameterException;

class RequestEnumEntity extends BaseRequestEntity
{
    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['enum_code'])) {
            throw new InvalidParameterException('Enum code parameter is required');
        }
        $options['class'] = ExtendHelper::buildEnumValueClassName($options['enum_code']);

        return parent::initialize($options);
    }
}
