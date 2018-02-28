<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Oro\Component\Layout\OptionValueBag;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class OptionValueBagNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * @var NormalizerInterface|DenormalizerInterface
     */
    protected $serializer;

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!($serializer instanceof NormalizerInterface && $serializer instanceof DenormalizerInterface)) {
            throw new \RuntimeException('OptionValueBagNormalizer is not compatible with provided serializer');
        }

        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof OptionValueBag;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $actions = [
            'add' => [],
            'replace' => [],
            'remove' => [],
        ];

        /** @var OptionValueBag $bag */
        $bag = $object;

        foreach ($bag->all() as $action) {
            switch ($action->getName()) {
                case 'add':
                    $actions['add'][] = [
                        $this->parseArgument($action->getArgument(0), $format, $context)
                    ];
                    break;
                case 'replace':
                    $actions['replace'][] = [
                        $this->parseArgument($action->getArgument(0), $format, $context),
                        $this->parseArgument($action->getArgument(1), $format, $context)
                    ];
                    break;
                case 'remove':
                    $actions['remove'][] = [
                        $this->parseArgument($action->getArgument(0), $format, $context)
                    ];
                    break;
            }
        }

        return [
            'type' => OptionValueBag::class,
            'actions' => $actions
        ];
    }

    /**
     * @param mixed       $argument
     * @param string|null $format
     * @param array       $context
     *
     * @return mixed
     */
    private function parseArgument($argument, $format = null, array $context = [])
    {
        if (is_object($argument)) {
            return $this->serializer->normalize($argument, $format, $context);
        }

        if (is_array($argument)) {
            array_walk_recursive(
                $argument,
                function (&$var) use ($format, $context) {
                    if (!is_scalar($var)) {
                        $var = $this->serializer->normalize($var, $format, $context);
                    }
                }
            );
        }

        return $argument;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === OptionValueBag::class;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $bag = new OptionValueBag();

        foreach ($data['actions'] as $key => $arguments) {
            foreach ($arguments as $argument) {
                $parsedElement = $argument[0];

                if (is_array($parsedElement)) {
                    $result = $this->denormalizeVarRecursive($parsedElement, $format, $context);
                    $argument = ($result) ? [$result] : $argument;
                }

                call_user_func_array([$bag, $key], $argument);
            }
        }

        return $bag;
    }

    /**
     * @param array  $element
     * @param string $format
     * @param array  $context
     *
     * @return mixed
     */
    protected function denormalizeVarRecursive(array &$element, $format, array $context)
    {
        if (array_key_exists('type', $element) && class_exists($element['type'])) {
            return $this->serializer->denormalize($element, $element['type'], $format, $context);
        } else {
            foreach ($element as &$value) {
                if (is_array($value)) {
                    if (array_key_exists('type', $value) && class_exists($value['type'])) {
                        $value = $this->serializer->denormalize($value, $value['type'], $format, $context);
                    } else {
                        $this->denormalizeVarRecursive($value, $format, $context);
                    }
                }
            }

            return $element;
        }
    }
}
