<?php

namespace Oro\Component\ChainProcessor;

/**
 * This applicable checker allows to use processor attributes to manage processors to be executed.
 * For example if you need to execute some processor only for specified entity type,
 * you can add 'class' attribute to this processor. As result it will be executed only if
 * a value of the 'class' attribute is equal of a value of 'class' attribute in the Context.
 * When several attributes are defined for a processor, it will be executed only if
 * values all of these attributes are equal to the corresponding attributes in the Context.
 */
class MatchApplicableChecker implements ApplicableCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result = self::APPLICABLE;
        foreach ($processorAttributes as $key => $value) {
            if ($key === 'group' || !is_scalar($value)) {
                continue;
            }
            if (!$context->has($key)) {
                $result = self::ABSTAIN;
            } elseif ($context->get($key) !== $value) {
                $result = self::NOT_APPLICABLE;
                break;
            }
        }

        return $result;
    }
}
