<?php

namespace Oro\Bundle\PlatformBundle\ErrorRenderer;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Removes the "message" property from the normalization result
 * when its value is just equals to a text representation of the "code" property.
 * @see \FOS\RestBundle\Serializer\Normalizer\FlattenExceptionNormalizer
 * This fix is required because such message does not have a sense and to be able to use a localized error messages.
 * Also it it is required to correct work of our JS error handlers,
 * such as showErrorInUI() in /platform/src/Oro/Bundle/UIBundle/Resources/public/js/error.js.
 */
class FixMessageFlattenExceptionNormalizer implements NormalizerInterface
{
    private const CODE = 'code';
    private const MESSAGE = 'message';

    private NormalizerInterface $innerNormalizer;

    public function __construct(NormalizerInterface $innerNormalizer)
    {
        $this->innerNormalizer = $innerNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        /** @var array $result */
        $result = $this->innerNormalizer->normalize($object, $format, $context);
        if (\is_array($result) && isset($result[self::CODE], $result[self::MESSAGE])) {
            $code = $result[self::CODE];
            $message = $result[self::MESSAGE];
            if ((isset(Response::$statusTexts[$code]) && $message === Response::$statusTexts[$code])
                || 'error' === $message
            ) {
                unset($result[self::MESSAGE]);
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $this->innerNormalizer->supportsNormalization($data, $format);
    }
}
