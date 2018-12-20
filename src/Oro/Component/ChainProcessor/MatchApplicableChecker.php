<?php

namespace Oro\Component\ChainProcessor;

/**
 * This applicable checker allows to use processor attributes to manage processors to be executed.
 * For example if you need to execute some processor only for specified entity type,
 * you can add 'class' attribute to this processor. As result it will be executed only if
 * a value of the 'class' attribute is equal of a value of 'class' attribute in the execution context.
 * When several attributes are defined for a processor, it will be executed only if
 * values all of these attributes are equal to the corresponding attributes in the execution context.
 */
class MatchApplicableChecker extends AbstractMatcher implements ApplicableCheckerInterface
{
    /** @var array [attribute name => true, ...] */
    private $ignoredAttributes;

    /**
     * @param string[] $ignoredAttributes
     */
    public function __construct(array $ignoredAttributes = ['group'])
    {
        $this->ignoredAttributes = \array_fill_keys($ignoredAttributes, true);
    }

    /**
     * @param string $attribute
     */
    public function addIgnoredAttribute($attribute)
    {
        $this->ignoredAttributes[$attribute] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result = self::APPLICABLE;
        foreach ($processorAttributes as $name => $value) {
            if (isset($this->ignoredAttributes[$name])) {
                continue;
            }
            if (!$context->has($name)) {
                $result = self::ABSTAIN;
            } elseif (!$this->isMatch($value, $context->get($name), $name)) {
                $result = self::NOT_APPLICABLE;
                break;
            }
        }

        return $result;
    }
}
