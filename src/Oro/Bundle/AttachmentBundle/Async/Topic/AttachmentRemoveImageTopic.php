<?php

namespace Oro\Bundle\AttachmentBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The topic to remove image files related to removed attachment related entities.
 */
class AttachmentRemoveImageTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro_attachment.remove_image';
    }

    public static function getDescription(): string
    {
        return 'Removes image files related to removed attachment related entities.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('images')
            ->setAllowedValues('images', 'array')
            ->setDefault('images', function (OptionsResolver $imageResolver) {
                $imageResolver
                    ->setPrototype(true)
                    ->setRequired([
                        'id',
                        'fileName',
                        'originalFileName',
                        'parentEntityClass',
                    ])
                    ->setNormalizer('originalFileName', function (Options $options, $value) {
                        return $value ?: $options['fileName'];
                    })
                    ->setAllowedTypes('id', ['int'])
                    ->setAllowedTypes('fileName', ['string'])
                    ->setAllowedTypes('originalFileName', ['string', 'null'])
                    ->setAllowedTypes('parentEntityClass', ['string']);
            })
            ->addAllowedValues('images', static function (array $value) {
                if (!$value) {
                    throw new InvalidOptionsException('The nested option "images" is expected to be not empty.');
                }

                return true;
            });
    }
}
