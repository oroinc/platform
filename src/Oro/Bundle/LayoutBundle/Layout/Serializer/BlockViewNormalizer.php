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
 * Normalizer for layout block view.
 */
class BlockViewNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    private const ID = 'id';
    private const BLOCK_TYPE = 'block_type';
    private const BLOCK_PREFIXES = 'block_prefixes';
    private const BLOCK = 'block';
    private const BLOCKS = 'blocks';

    private const DENORMALIZE_RECURSIVE_CALL = 'blockViewDenormalizeRecursiveCall';

    private const DATA_KEYS = 'k';
    private const DATA_VARS = 'v';
    private const DATA_CHILDREN = 'c';
    private const DATA_TYPE = 't';
    private const DATA_VALUE = 'v';

    /** @var BlockViewVarsNormalizerInterface */
    private $varsNormalizer;

    /** @var TypeNameConverter */
    private $typeNameConverter;

    /** @var BlockView[] */
    private $currentDenormalizedViews = [];

    /** @var NormalizerInterface|DenormalizerInterface */
    protected $serializer;

    public function __construct(
        BlockViewVarsNormalizerInterface $varsNormalizer,
        TypeNameConverter $typeNameConverter
    ) {
        $this->varsNormalizer = $varsNormalizer;
        $this->typeNameConverter = $typeNameConverter;
    }

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
    public function normalize($object, string $format = null, array $context = [])
    {
        /** @var BlockView $object */

        $data = [];

        if (!empty($object->vars)) {
            $vars = $object->vars;
            unset($vars[self::BLOCK], $vars[self::BLOCKS]);
            $this->varsNormalizer->normalize($vars, $context);
            array_walk_recursive(
                $vars,
                function (&$var) use ($format, $context) {
                    if (\is_object($var)) {
                        if ($var instanceof BlockView) {
                            throw new UnexpectedBlockViewVarTypeException(
                                'BlockView vars cannot contain link to another BlockView'
                            );
                        }

                        $className = \get_class($var);
                        $var = [
                            self::DATA_TYPE => $this->typeNameConverter->getShortTypeName($className) ?? $className,
                            self::DATA_VALUE => $this->serializer->normalize($var, $format, $context),
                        ];
                    }
                }
            );
            $data[self::DATA_KEYS] = [$vars[self::ID], $vars[self::BLOCK_TYPE], $vars[self::BLOCK_PREFIXES] ?? []];
            unset($vars[self::ID], $vars[self::BLOCK_TYPE], $vars[self::BLOCK_PREFIXES]);
            if (!empty($vars)) {
                $data[self::DATA_VARS] = $vars;
            }
        }

        foreach ($object->children as $childView) {
            $data[self::DATA_CHILDREN][] = $this->normalize($childView, $format, $context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return BlockView::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $view = new BlockView();
        [$id, $blockType, $blockPrefixes] = $data[self::DATA_KEYS];

        $recursiveCall = \array_key_exists(self::DENORMALIZE_RECURSIVE_CALL, $context);
        if (!$recursiveCall) {
            $context[self::DENORMALIZE_RECURSIVE_CALL] = true;
            $this->currentDenormalizedViews = [$id => $view];
        }

        $view->vars = $data[self::DATA_VARS] ?? [];
        $view->vars[self::ID] = $id;
        $view->vars[self::BLOCK_TYPE] = $blockType;
        $view->vars[self::BLOCK_PREFIXES] = $blockPrefixes;
        $this->denormalizeVarRecursive($view->vars, $format, $context);
        $this->varsNormalizer->denormalize($view->vars, $context);

        if (\array_key_exists(self::DATA_CHILDREN, $data)) {
            foreach ($data[self::DATA_CHILDREN] as $childData) {
                $childView = $this->denormalize($childData, $type, $format, $context);
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

    protected function setBlocksRecursive(BlockView $view, BlockViewCollection $blocks)
    {
        $view->blocks = $blocks;
        $view->vars[self::BLOCK] = $view;
        $view->vars[self::BLOCKS] = $blocks;

        foreach ($view->children as $childView) {
            $this->setBlocksRecursive($childView, $blocks);
        }
    }

    protected function denormalizeVarRecursive(array &$var, ?string $format, array $context)
    {
        foreach ($var as &$value) {
            if (!\is_array($value)) {
                continue;
            }
            if (\array_key_exists(self::DATA_TYPE, $value)) {
                $type = $this->typeNameConverter->getTypeName($value[self::DATA_TYPE]) ?? $value[self::DATA_TYPE];
                if (class_exists($type)) {
                    $value = $this->serializer->denormalize($value[self::DATA_VALUE], $type, $format, $context);
                    continue;
                }
            }
            $this->denormalizeVarRecursive($value, $format, $context);
        }
    }
}
