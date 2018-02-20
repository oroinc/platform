<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Serializer\ProcessDataSerializer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

abstract class AbstractProcessNormalizer extends SerializerAwareNormalizer implements
    NormalizerInterface,
    DenormalizerInterface
{
    /**
     * @var ProcessDataSerializer
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
