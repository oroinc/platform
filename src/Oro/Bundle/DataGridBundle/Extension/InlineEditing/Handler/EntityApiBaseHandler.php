<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor\EntityApiHandlerProcessor;
use Symfony\Component\Form\FormInterface;

class EntityApiBaseHandler
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Registry
     */
    protected $processor;

    /**
     * @param Registry $registry
     * @param EntityApiHandlerProcessor $processor
     */
    public function __construct(Registry $registry, EntityApiHandlerProcessor $processor)
    {
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
     * @return bool True on successful processing, false otherwise
     */
    public function process($entity, FormInterface $form, $data, $method)
    {
        $handler = $this->processor->getHandlerByClass(ClassUtils::getClass($entity));

        if ($handler) {
            $handler->preProcess($entity);
        }
        $form->setData($entity);

        if (
            count($data) > 1 // refactor
            && in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
            $form->submit($data);

            if ($form->isValid()) {
                if ($handler) {
                    $handler->onProcess($entity);
                }
                $this->onSuccess($entity);
                if ($handler) {
                    $handler->afterProcess($entity);
                }

                return true;
            }
        }

        return false;
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
