<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Oro\Component\Layout\OptionValueBag;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class OptionValueBagNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $data instanceof OptionValueBag;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
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
                    $actions['add'][] = [$action->getArgument(0)];
                    break;
                case 'replace':
                    $actions['replace'][] = [$action->getArgument(0), $action->getArgument(1)];
                    break;
                case 'remove':
                    $actions['remove'][] = [$action->getArgument(0)];
                    break;
            }
        }

        return [
            'type' => OptionValueBag::class,
            'actions' => $actions
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type == OptionValueBag::class;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $bag = new OptionValueBag();

        foreach ($data['actions'] as $key => $arguments) {
            foreach ($arguments as $argument) {
                call_user_func_array([$bag, $key], $argument);
            }
        }

        return $bag;
    }
}
