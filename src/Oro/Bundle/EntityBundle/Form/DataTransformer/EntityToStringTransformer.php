<?php

namespace Oro\Bundle\EntityBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EntityToStringTransformer implements DataTransformerInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return $value;
        }

        $data = json_decode($value, true);

        return $this->doctrineHelper->getEntityReference($data['entityClass'], $data['entityId']);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return $value;
        }

        return json_encode([
            'entityClass' => ClassUtils::getClass($value),
            'entityId'    => $this->doctrineHelper->getSingleEntityIdentifier($value),
        ]);
    }
}
