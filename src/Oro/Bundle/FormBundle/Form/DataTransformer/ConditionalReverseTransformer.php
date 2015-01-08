<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * This transformer can be used as a wrapper for any other transformer in case
 * when you need to perform reverse transformation only in certain cases.
 * For example you can use it in case then some control can be disabled on client side.
 */
class ConditionalReverseTransformer implements DataTransformerInterface
{
    /** @var DataTransformerInterface */
    protected $baseTransformer;

    /**
     * The callback used for check whether a reverse transformation is required or not
     * If this callback returns true the reverse transformation is performed;
     * otherwise, original value is returned
     *
     * @var callable
     */
    private $condition;

    /**
     * @param DataTransformerInterface $baseTransformer
     * @param callable                 $condition
     */
    public function __construct(
        DataTransformerInterface $baseTransformer,
        $condition
    ) {
        if (!is_callable($condition)) {
            throw new \InvalidArgumentException('Argument $condition should be a callable');
        }

        $this->baseTransformer = $baseTransformer;
        $this->condition       = $condition;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $this->baseTransformer->transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!call_user_func($this->condition, $value)) {
            return $value;
        }

        return $this->baseTransformer->reverseTransform($value);
    }
}
