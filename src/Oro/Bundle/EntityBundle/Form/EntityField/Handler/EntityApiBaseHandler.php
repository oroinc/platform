<?php

namespace Oro\Bundle\EntityBundle\Form\EntityField\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor\EntityApiHandlerProcessor;

class EntityApiBaseHandler
{
    /** @var Registry */
    protected $registry;

    /** @var EntityApiHandlerProcessor */
    protected $processor;

    /**
     * @param Registry $registry
     * @param EntityApiHandlerProcessor $processor
     */
    public function __construct(
        Registry $registry,
        EntityApiHandlerProcessor $processor
    ) {
        $this->registry = $registry;
        $this->processor = $processor;
    }

    /**
     * Process form
     *
     * @param $entity
     * @param FormInterface $form
     * @param array $data
     * @param string $method
     *
     * @return array changset
     */
    public function process($entity, FormInterface $form, $data, $method)
    {
        $this->processor->preProcess($entity);
        $form->setData($entity);

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $form->submit($data);

            if ($form->isValid()) {
                $this->processor->beforeProcess($entity);

                $this->onSuccess($entity);

                $changeSet = $this->initChangeSet($entity, $data);
                $changeSet = $this->updateChangeSet($changeSet, $this->processor->afterProcess($entity));

                return $changeSet;
            } else {
                $this->processor->invalidateProcess($entity);
            }
        }

        return [];
    }

    /**
     * @param $entity
     *
     * @return array
     */
    protected function initChangeSet($entity, $data)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $response = [
            'fields' => $data
        ];
        if ($accessor->isReadable($entity, 'updatedAt')) {
            $response['fields']['updatedAt'] = $accessor->getValue($entity, 'updatedAt');
        }

        return $response;
    }

    /**
     * @param $changeSet
     * @param $update
     *
     * @return array
     */
    protected function updateChangeSet($changeSet, $update)
    {
        if (is_array($update)) {
            $result = array_replace_recursive($changeSet, $update);
        } else {
            $result = $changeSet;
        }

        return $result;
    }

    /**
     * "Success" form handler
     *
     * @param $entity
     */
    protected function onSuccess($entity)
    {
        $this->registry->getManager()->persist($entity);
        $this->registry->getManager()->flush();
    }
}
