<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExpressionNormalizer implements NormalizerInterface, DenormalizerInterface
{
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

        return [
            'expression' => (string)$parsedExpression,
            'nodes' => serialize($parsedExpression->getNodes())
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
        return new ParsedExpression(
            $data['expression'],
            unserialize($data['nodes'])
        );
    }
}
