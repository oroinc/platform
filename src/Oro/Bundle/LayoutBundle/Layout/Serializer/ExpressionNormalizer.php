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
        return is_object($data) && $data instanceof ParsedExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var ParsedExpression $parsedExpression */
        $parsedExpression = $object;

        return [
            'type' => ParsedExpression::class,
            'value' => [
                'expression' => (string)$parsedExpression,
                'nodes' => serialize($parsedExpression->getNodes())
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type == ParsedExpression::class;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return new ParsedExpression(
            $data['value']['expression'],
            unserialize($data['value']['nodes'])
        );
    }
}
