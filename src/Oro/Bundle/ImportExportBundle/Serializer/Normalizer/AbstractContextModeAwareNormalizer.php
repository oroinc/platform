<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Base context mode aware normalizer.
 */
abstract class AbstractContextModeAwareNormalizer implements
    NormalizerInterface,
    DenormalizerInterface
{
    protected array $availableModes = [];

    protected ?string $defaultMode = null;

    public function __construct(array $availableModes, ?string $defaultMode = null)
    {
        $this->setAvailableModes($availableModes);
        if (null !== $defaultMode) {
            $this->setDefaultMode($defaultMode);
        }
    }

    /**
     * Normalization depends on mode
     *
     */
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $mode = $this->getMode($context);
        $method = 'normalize' . ucfirst($mode);
        if (method_exists($this, $method)) {
            return $this->$method($object, $format, $context);
        }
        throw new RuntimeException(sprintf('Normalization with mode "%s" is not supported', $mode));
    }

    /**
     * Denormalization depends on mode
     *
     */
    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        $mode = $this->getMode($context);
        $method = 'denormalize' . ucfirst($mode);
        if (method_exists($this, $method)) {
            return $this->$method($data, $type, $format, $context);
        }
        throw new RuntimeException(sprintf('Denormalization with mode "%s" is not supported', $mode));
    }

    /**
     * @throws RuntimeException
     */
    protected function getMode(array $context): ?string
    {
        $mode = $context['mode'] ?? $this->defaultMode;
        if (!in_array($mode, $this->availableModes, true)) {
            throw new RuntimeException(sprintf('Mode "%s" is not supported', $mode));
        }

        return $mode;
    }

    /**
     * @param array $modes
     *
     * @return AbstractContextModeAwareNormalizer
     * @throws RuntimeException
     */
    protected function setAvailableModes(array $modes): self
    {
        if (!$modes) {
            throw new RuntimeException('Modes must be an array with at least one element');
        }

        $this->availableModes = $modes;
        $this->setDefaultMode(reset($modes));

        return $this;
    }

    /**
     * @param string $mode
     *
     * @return AbstractContextModeAwareNormalizer
     * @throws RuntimeException
     */
    protected function setDefaultMode(string $mode): self
    {
        if (!in_array($mode, $this->availableModes, true)) {
            throw new RuntimeException(
                sprintf(
                    'Mode "%s" is not supported, available modes are "%s"',
                    $mode,
                    implode('", ', $this->availableModes)
                )
            );
        }
        $this->defaultMode = $mode;

        return $this;
    }
}
