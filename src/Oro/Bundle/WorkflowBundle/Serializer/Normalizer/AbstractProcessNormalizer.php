<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

abstract class AbstractProcessNormalizer implements
    SerializerAwareInterface,
    NormalizerInterface,
    DenormalizerInterface
{
    use SerializerAwareTrait;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @param array $context
     * @return ProcessJob
     * @throws \LogicException
     */
    protected function getProcessJob(array $context)
    {
        if (empty($context['processJob'])) {
            throw new \LogicException('Process job is not defined');
        }

        if (!$context['processJob'] instanceof ProcessJob) {
            throw new \LogicException('Invalid process job entity');
        }

        return $context['processJob'];
    }

    /**
     * @param object $entity
     * @return string
     */
    protected function getClass($entity)
    {
        return ClassUtils::getClass($entity);
    }
}
