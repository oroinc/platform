<?php

namespace Oro\Bundle\ApiBundle\Command\Util;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\MatchApplicableChecker;

class AttributesApplicableChecker extends MatchApplicableChecker
{
    /** @var string[] */
    protected $attributes;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct();
        $this->attributes = $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result = self::ABSTAIN;

        foreach ($this->attributes as $attrName) {
            if (array_key_exists($attrName, $processorAttributes) && $context->has($attrName)
                && !$this->isMatch($processorAttributes[$attrName], $context->get($attrName), $attrName)
            ) {
                $result = self::NOT_APPLICABLE;
            }
        }

        return $result;
    }
}
