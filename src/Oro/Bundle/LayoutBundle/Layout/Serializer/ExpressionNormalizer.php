<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCache;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\SerializedParsedExpression;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for parsed expressions.
 */
class ExpressionNormalizer implements
    NormalizerInterface,
    DenormalizerInterface,
    TypeNameConverterInterface
{
    private const SHORT_TYPE = 'e';

    private const DATA_EXPRESSION = 'e';
    private const DATA_EXTRA_VARIABLES = 'v';
    private const DATA_NODES = 'n';

    private ExpressionLanguageCache $expressionLanguageCache;

    public function __construct(ExpressionLanguageCache $expressionLanguageCache)
    {
        $this->expressionLanguageCache = $expressionLanguageCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortTypeName(string $type): ?string
    {
        return ParsedExpression::class === $type
            ? self::SHORT_TYPE
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeName(string $shortType): ?string
    {
        return self::SHORT_TYPE === $shortType
            ? ParsedExpression::class
            : null;
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
    public function normalize($object, string $format = null, array $context = [])
    {
        /** @var ParsedExpression $object */

        $expression = (string)$object;
        $closure = $this->expressionLanguageCache->getClosure($expression);
        if (null !== $closure) {
            return [
                self::DATA_EXPRESSION => $expression,
            ];
        }
        $closureWithExtraParams = $this->expressionLanguageCache->getClosureWithExtraParams($expression);
        if (null !== $closureWithExtraParams) {
            return [
                self::DATA_EXPRESSION => $expression,
                self::DATA_EXTRA_VARIABLES => $closureWithExtraParams->getExtraParamNames()
            ];
        }

        return [
            self::DATA_EXPRESSION => $expression,
            self::DATA_NODES => serialize($object->getNodes()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return ParsedExpression::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (\array_key_exists(self::DATA_NODES, $data)) {
            return new SerializedParsedExpression($data[self::DATA_EXPRESSION], $data[self::DATA_NODES]);
        }

        if (\array_key_exists(self::DATA_EXTRA_VARIABLES, $data)) {
            return $this->expressionLanguageCache->getClosureWithExtraParams($data[self::DATA_EXPRESSION]);
        }

        return $this->expressionLanguageCache->getClosure($data[self::DATA_EXPRESSION]);
    }
}
