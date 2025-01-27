<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Transforms {@see \DateTimeInterface} object to the datetime string acceptable in import/export.
 */
class DateTimeToImportExportStringTransformer implements DataTransformerInterface
{
    public function __construct(private NormalizerInterface&DenormalizerInterface $normalizer)
    {
    }

    #[\Override]
    public function transform(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface.');
        }

        return $this->normalizer->normalize($value, null, ['type' => 'datetime']);
    }

    #[\Override]
    public function reverseTransform(mixed $value): ?\DateTimeInterface
    {
        if (empty($value)) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        try {
            return $this->normalizer->denormalize($value, 'DateTime', null, ['type' => 'datetime']);
        } catch (\Throwable $exception) {
            throw new TransformationFailedException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
