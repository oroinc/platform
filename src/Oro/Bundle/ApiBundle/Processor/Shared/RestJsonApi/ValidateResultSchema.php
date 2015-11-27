<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\RestJsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Normalizer\RestJsonApi\ResultUtil;

class ValidateResultSchema implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $result = $context->getResult();
        if (!is_array($result)) {
            throw new \RuntimeException('The result must be an array.');
        }

        $rootSections = [ResultUtil::DATA, ResultUtil::ERRORS, ResultUtil::META];
        if (count(array_intersect(array_keys($result), $rootSections)) === 0) {
            throw new \RuntimeException(
                sprintf(
                    'The result must contain at least one of the following sections: %s.',
                    implode(', ', $rootSections)
                )
            );
        }

        if (array_key_exists(ResultUtil::DATA, $result) && array_key_exists(ResultUtil::ERRORS, $result)) {
            throw new \RuntimeException(
                sprintf(
                    'The sections "%s" and "%s" must not coexist in the result.',
                    ResultUtil::DATA,
                    ResultUtil::ERRORS
                )
            );
        }

        if (array_key_exists(ResultUtil::INCLUDED, $result) && !array_key_exists(ResultUtil::DATA, $result)) {
            throw new \RuntimeException(
                sprintf(
                    'The result can contain the "%s" section only together with the "%s" section.',
                    ResultUtil::INCLUDED,
                    ResultUtil::DATA
                )
            );
        }
    }
}
