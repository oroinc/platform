<?php

namespace Oro\Component\Layout\ExpressionLanguage\Encoder;

class ExpressionEncoderRegistry
{
    /**
     * @var ExpressionEncoderInterface[]
     */
    protected $encoders;

    /**
     * @param ExpressionEncoderInterface[] $encoders ex.: ['json' => new JsonExpressionEncoder()]
     */
    public function __construct(array $encoders)
    {
        $this->encoders = $encoders;
    }

    /**
     * Returns the encoder for the given format
     *
     * @param string $format
     * @return ExpressionEncoderInterface
     * @throws \RuntimeException if the appropriate encoder does not exist
     */
    public function get($format)
    {
        if (!array_key_exists($format, $this->encoders)) {
            throw new \RuntimeException(
                sprintf('The expression encoder for "%s" formatting was not found.', $format)
            );
        }

        return $this->encoders[$format];
    }
}
