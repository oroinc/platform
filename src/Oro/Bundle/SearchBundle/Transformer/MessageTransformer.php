<?php

namespace Oro\Bundle\SearchBundle\Transformer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class MessageTransformer implements MessageTransformerInterface
{
    /**
     * The maximum number of IDs per one reindex request message
     */
    const CHUNK_SIZE = 100;

    /** @var DoctrineHelper */
    private $doctrineHelper;

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
    public function transform(array $entities, array $context = [])
    {
        $messages = [];

        $buffer = [];
        foreach ($entities as $entity) {
            $class = $this->doctrineHelper->getEntityClass($entity);
            $id = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            if ($id === null) {
                throw new \LogicException("You cant reindex entity '$class' with null id");
            }

            if (!isset($buffer[$class])) {
                $buffer[$class] = [];
            }

            $buffer[$class][self::MESSAGE_FIELD_ENTITY_CLASS] = $class;
            $buffer[$class][self::MESSAGE_FIELD_ENTITY_IDS][$id] = $id;

            $entityIds = $buffer[$class][self::MESSAGE_FIELD_ENTITY_IDS];
            if (count($entityIds) >= self::CHUNK_SIZE) {
                $messages[] = $buffer[$class];
                unset($buffer[$class]);
            }
        }

        foreach ($buffer as $message) {
            $messages[] = $message;
        }

        return $messages;
    }
}
