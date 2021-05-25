<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCache;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\SerializedParsedExpression;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for parsed expressions
 */
class ExpressionNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private ExpressionLanguageCache $expressionLanguageCache;

    public function __construct(ExpressionLanguageCache $expressionLanguageCache)
    {
        $this->expressionLanguageCache = $expressionLanguageCache;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof ParsedExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        /** @var ParsedExpression $parsedExpression */
        $parsedExpression = $object;

        $expression = (string)$parsedExpression;
        $closure = $this->expressionLanguageCache->getClosure($expression);
        if (null !== $closure) {
            return [
                'expression' => $expression,
            ];
        }

        return [
            'expression' => $expression,
            'nodes' => serialize($parsedExpression->getNodes()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === ParsedExpression::class;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!array_key_exists('nodes', $data)) {
            return $this->expressionLanguageCache->getClosure($data['expression']);
        }

        return new SerializedParsedExpression(
            $data['expression'],
            $data['nodes']
        );
    }
}
