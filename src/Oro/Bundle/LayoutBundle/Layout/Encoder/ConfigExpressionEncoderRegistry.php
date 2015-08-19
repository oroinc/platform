<?php

namespace Oro\Bundle\LayoutBundle\Layout\Encoder;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigExpressionCompilerPass;

class ConfigExpressionEncoderRegistry
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string[] */
    protected $encoders;

    /**
     * @param ContainerInterface $container
     * @param string[]           $encoders
     */
    public function __construct(ContainerInterface $container, array $encoders)
    {
        $this->container = $container;
        $this->encoders  = $encoders;
    }

    /**
     * Returns the encoder for the given format
     *
     * @param string $format
     *
     * @return ConfigExpressionEncoderInterface
     *
     * @throws \RuntimeException if the appropriate encoder does not exist
     */
    public function getEncoder($format)
    {
        if (!isset($this->encoders[$format])) {
            throw new \RuntimeException(
                sprintf(
                    'The expression encoder for "%s" formatting was not found. '
                    . 'Check that the appropriate encoder service is registered in '
                    . 'the container and marked by tag "%s".',
                    $format,
                    ConfigExpressionCompilerPass::EXPRESSION_ENCODER_TAG
                )
            );
        }

        return $this->container->get($this->encoders[$format]);
    }
}
