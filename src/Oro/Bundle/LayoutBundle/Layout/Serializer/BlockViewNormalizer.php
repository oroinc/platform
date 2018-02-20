<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Oro\Bundle\LayoutBundle\Exception\UnexpectedBlockViewVarTypeException;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCollection;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class BlockViewNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * @var BlockView[]
     */
    private $currentDenormalizedViews = [];

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
    public function normalize($object, $format = null, array $context = [])
    {
        /** @var BlockView $view */
        $view = $object;

        $data = [];

        if (!empty($view->vars)) {
            $data['vars'] = $view->vars;

            unset($data['vars']['block'], $data['vars']['blocks']);

            array_walk_recursive(
                $data['vars'],
                function (&$var) use ($format, $context) {
                    if (is_object($var)) {
                        if ($var instanceof BlockView) {
                            throw new UnexpectedBlockViewVarTypeException(
                                'BlockView vars cannot contain link to another BlockView'
                            );
                        }

                        $var = [
                            'type' => get_class($var),
                            'value' => $this->serializer->normalize($var, $format, $context),
                        ];
                    }
                }
            );
        }

        foreach ($view->children as $childId => $childView) {
            $data['children'][$childId] = $this->normalize($childView, $format, $context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === BlockView::class;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $view = new BlockView();
        $view->vars['id'] = $data['vars']['id'];

        $recursiveCall = array_key_exists('blockViewDenormalizeRecursiveCall', $context);
        if (!$recursiveCall) {
            $context['blockViewDenormalizeRecursiveCall'] = true;
            $this->currentDenormalizedViews = [
                $view->getId() => $view
            ];
        }

        if (array_key_exists('vars', $data)) {
            $view->vars = $data['vars'];
            $this->denormalizeVarRecursive($view->vars, $format, $context);
        }

        if (array_key_exists('children', $data)) {
            foreach ($data['children'] as $childId => $childData) {
                $childView = $this->denormalize($childData, $class, $format, $context);
                $childView->parent = $view;

                $this->currentDenormalizedViews[$childView->getId()] = $childView;

                $view->children[$childId] = $childView;
            }
        }

        if (!$recursiveCall) {
            $this->setBlocksRecursive($view, new BlockViewCollection($this->currentDenormalizedViews));
        }

        return $view;
    }

    /**
     * @param BlockView $view
     * @param BlockViewCollection $blocks
     */
    private function setBlocksRecursive($view, BlockViewCollection $blocks)
    {
        $view->blocks = $blocks;
        $view->vars['block'] = $view;
        $view->vars['blocks'] = $blocks;

        foreach ($view->children as $childView) {
            $this->setBlocksRecursive($childView, $blocks);
        }
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
