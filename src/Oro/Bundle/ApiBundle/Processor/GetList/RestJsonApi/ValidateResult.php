<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\RestJsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Normalizer\RestJsonApi\ResultUtil;

class ValidateResult implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $result = $context->getResult();
        if (array_key_exists(ResultUtil::DATA, $result) && !is_array($result[ResultUtil::DATA])) {
            throw new \RuntimeException(
                sprintf('The "%s" section must be an array.', ResultUtil::DATA)
            );
        }
    }
}
