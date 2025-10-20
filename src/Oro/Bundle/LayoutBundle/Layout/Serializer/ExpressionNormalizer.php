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

    #[\Override]
    public function getShortTypeName(string $type): ?string
    {
        return ParsedExpression::class === $type
            ? self::SHORT_TYPE
            : null;
    }

    #[\Override]
    public function getTypeName(string $shortType): ?string
    {
        return self::SHORT_TYPE === $shortType
            ? ParsedExpression::class
            : null;
    }

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof ParsedExpression;
    }

    #[\Override]
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        /** @var ParsedExpression $data */

        $expression = (string)$data;
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
            self::DATA_NODES => serialize($data->getNodes()),
        ];
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return ParsedExpression::class === $type;
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (\array_key_exists(self::DATA_NODES, $data)) {
            return new SerializedParsedExpression($data[self::DATA_EXPRESSION], $data[self::DATA_NODES]);
        }

        if (\array_key_exists(self::DATA_EXTRA_VARIABLES, $data)) {
            return $this->expressionLanguageCache->getClosureWithExtraParams($data[self::DATA_EXPRESSION]);
        }

        return $this->expressionLanguageCache->getClosure($data[self::DATA_EXPRESSION]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ParsedExpression::class => false
        ];
    }
}
