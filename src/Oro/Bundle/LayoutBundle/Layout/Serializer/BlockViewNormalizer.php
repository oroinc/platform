<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

use Oro\Component\Layout\BlockView;

class BlockViewNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
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
            throw new \RuntimeException('BlockViewNormalizer is not compatible with provided serializer');
        }

        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof BlockView;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var BlockView $view */
        $view = $object;

        $data = [];

        if (!empty($view->vars)) {
            $data['vars'] = $view->vars;
            array_walk_recursive(
                $data['vars'],
                function (&$var) use ($format, $context) {
                    if (is_object($var)) {
                        $var = [
                            'type' => get_class($var),
                            'value' => $this->serializer->normalize($var, $format, $context),
                        ];
                    }
                }
            );
        }

        foreach ($view->children as $childView) {
            $data['children'][] = $this->normalize($childView, $format, $context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type == BlockView::class;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $view = new BlockView();

        if (array_key_exists('vars', $data)) {
            $view->vars = $data['vars'];
            $this->denormalizeVarRecursive($view->vars, $format, $context);
        }

        if (array_key_exists('children', $data)) {
            foreach ($data['children'] as $childData) {
                $childView = $this->denormalize($childData, $class, $format, $context);
                $childView->parent = $view;

                $view->children[] = $childView;
            }
        }

        return $view;
    }

    /**
     * @param array $var
     * @param string $format
     * @param array $context
     */
    protected function denormalizeVarRecursive(array &$var, $format, array $context)
    {
        foreach ($var as $key => &$value) {
            if (is_array($value)) {
                if (array_key_exists('type', $value) && class_exists($value['type'])) {
                    $value = $this->serializer->denormalize($value['value'], $value['type'], $format, $context);
                } else {
                    $this->denormalizeVarRecursive($value, $format, $context);
                }
            }
        }
    }
}
