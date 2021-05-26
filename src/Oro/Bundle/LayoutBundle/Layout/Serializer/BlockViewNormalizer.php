<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Oro\Bundle\LayoutBundle\Exception\UnexpectedBlockViewVarTypeException;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCollection;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Normalizer for layout block view
 */
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
    public function supportsNormalization($data, $format = null): bool
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
            $this->unsetDefaults($data);

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
            $data['children'][] = $this->normalize($childView, $format, $context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
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
        $this->populateDefaults($view);

        if (array_key_exists('children', $data)) {
            foreach ($data['children'] as $childData) {
                $childView = $this->denormalize($childData, $class, $format, $context);
                $childId = $childView->getId();
                $childView->parent = $view;

                $this->currentDenormalizedViews[$childId] = $childView;

                $view->children[$childId] = $childView;
            }
        }

        if (!$recursiveCall) {
            $this->setBlocksRecursive($view, new BlockViewCollection($this->currentDenormalizedViews));
        }

        return $view;
    }

    protected function unsetDefaults(array &$data): void
    {
        unset($data['vars']['block'], $data['vars']['blocks']);
        if (array_key_exists('visible', $data['vars']) && $data['vars']['visible'] === true) {
            unset($data['vars']['visible']);
        }
        if (array_key_exists('hidden', $data['vars']) && $data['vars']['hidden'] === false) {
            unset($data['vars']['hidden']);
        }
        if (array_key_exists('attr', $data['vars']) && empty($data['vars']['attr'])) {
            unset($data['vars']['attr']);
        }
        if (array_key_exists('translation_domain', $data['vars']) &&
            $data['vars']['translation_domain'] === 'messages') {
            unset($data['vars']['translation_domain']);
        }
    }

    protected function populateDefaults(BlockView $view): void
    {
        if (!array_key_exists('visible', $view->vars)) {
            $view->vars['visible'] = true;
        }
        if (!array_key_exists('hidden', $view->vars)) {
            $view->vars['hidden'] = false;
        }
        if (!array_key_exists('attr', $view->vars)) {
            $view->vars['attr'] = [];
        }
        if (!array_key_exists('translation_domain', $view->vars)) {
            $view->vars['translation_domain'] = 'messages';
        }
    }

    protected function setBlocksRecursive(BlockView $view, BlockViewCollection $blocks)
    {
        $view->blocks = $blocks;
        $view->vars['block'] = $view;
        $view->vars['blocks'] = $blocks;

        foreach ($view->children as $childView) {
            $this->setBlocksRecursive($childView, $blocks);
        }
    }

    protected function denormalizeVarRecursive(array &$var, ?string $format, array $context)
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
