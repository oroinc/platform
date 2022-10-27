<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\IdToEntityTransformer;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Provides functionality to select an existing integration channel.
 */
class ConfigIntegrationSelectType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new IdToEntityTransformer($this->registry, Channel::class));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return IntegrationSelectType::class;
    }
}
