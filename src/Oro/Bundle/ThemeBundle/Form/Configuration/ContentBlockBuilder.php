<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Represents builder for content_block_selector option
 */
class ContentBlockBuilder extends AbstractConfigurationChildBuilder
{
    public function __construct(private DataTransformerInterface $dataTransformer)
    {
    }

    #[\Override] public static function getType(): string
    {
        return 'content_block_selector';
    }

    #[\Override] public function supports(array $option): bool
    {
        return $option['type'] === self::getType();
    }

    #[\Override] protected function getTypeClass(): string
    {
        return ContentBlockSelectType::class;
    }

    #[\Override] protected function getDefaultOptions(): array
    {
        return [];
    }

    #[\Override] public function buildOption(FormBuilderInterface $builder, array $option): void
    {
        parent::buildOption($builder, $option);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($data) use ($option) {
                if (!isset($data[$option['name']]) || is_object($data[$option['name']])) {
                    return $data;
                }

                $object = $this->dataTransformer->reverseTransform($data[$option['name']]);

                $data[$option['name']] = $object;

                return $data;
            },
            function ($data) use ($option) {
                if (isset($data[$option['name']]) && is_object($data[$option['name']])) {
                    $data[$option['name']] = $this->dataTransformer->transform($data[$option['name']]);
                }
                return $data;
            }
        ));
    }
}
