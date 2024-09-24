<?php

namespace Oro\Bundle\EntityExtendBundle\Model\Action;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\Action\Action\RequestEntity as BaseRequestEntity;
use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * Request enum entity model action.
 */
class RequestEnumEntity extends BaseRequestEntity
{
    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options['enum_code'])) {
            throw new InvalidParameterException('Enum code parameter is required');
        }
        $options['class'] = EnumOption::class;

        return parent::initialize($options);
    }
}
