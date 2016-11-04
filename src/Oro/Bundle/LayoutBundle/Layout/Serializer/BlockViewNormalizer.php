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
     * @var SerializerInterface|NormalizerInterface|DenormalizerInterface
     */
    protected $serializer;

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!($serializer instanceof NormalizerInterface && $serializer instanceof DenormalizerInterface)) {
            throw new \RuntimeException('BlockViewNormalizer is incompatible with provided serializer');
        }

        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $data instanceof BlockView;
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
            foreach ($view->vars as $key => $value) {
                if (is_object($value)) {
                    $data['vars'][$key] = $this->serializer->normalize($value, $format, $context);
                } else {
                    $data['vars'][$key] = $value;
                }
            }
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
            foreach ($data['vars'] as $key => $value) {
                if (is_array($value) && array_key_exists('type', $value) && class_exists($value['type'])) {
                    $view->vars[$key] = $this->serializer->denormalize(
                        $value,
                        $value['type'],
                        $format,
                        $context
                    );
                } else {
                    $view->vars[$key] = $value;
                }
            }
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
}
