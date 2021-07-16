<?php

namespace Oro\Bundle\LayoutBundle\Console\Descriptor;

use Oro\Component\Layout\BlockTypeInterface;
use Symfony\Component\Console\Descriptor\DescriptorInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Abstract descriptor for {@see \Oro\Bundle\LayoutBundle\Command\DebugCommand}
 */
abstract class AbstractDescriptor implements DescriptorInterface
{
    /**
     * @var SymfonyStyle
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    public function describe(OutputInterface $output, $object, array $options = [])
    {
        $this->output = $output;

        if (null === $object) {
            $this->describeDefaults($options);
        } elseif ($object instanceof BlockTypeInterface) {
            $this->describeBlockType($object, $options);
        } elseif (is_object($object)) {
            $this->describeDataProvider($object, $options);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Object of type "%s" is not describable.', \get_class($object))
            );
        }
    }

    abstract protected function describeDefaults(array $options): void;

    abstract protected function describeBlockType(BlockTypeInterface $blockType, array $options = []): void;

    /**
     * @param object $dataProvider
     * @param array  $options
     */
    abstract protected function describeDataProvider($dataProvider, array $options = []): void;
}
