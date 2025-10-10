<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Oro\Component\Layout\OptionValueBag;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Normalizer for options value
 */
class OptionValueBagNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * @var NormalizerInterface|DenormalizerInterface
     */
    protected $serializer;

    #[\Override]
    public function setSerializer(SerializerInterface $serializer): void
    {
        if (!($serializer instanceof NormalizerInterface && $serializer instanceof DenormalizerInterface)) {
            throw new \RuntimeException('OptionValueBagNormalizer is not compatible with provided serializer');
        }

        $this->serializer = $serializer;
    }

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof OptionValueBag;
    }

    #[\Override]
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $actions = [
            'add' => [],
            'replace' => [],
            'remove' => [],
        ];

        /** @var OptionValueBag $bag */
        $bag = $data;

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

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === OptionValueBag::class;
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
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
    protected function denormalizeVarRecursive(array &$element, $format, array $context): mixed
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

    public function getSupportedTypes(?string $format): array
    {
        return [
            OptionValueBag::class => false
        ];
    }
}
