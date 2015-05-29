<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Soap;

use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\FormHandlerAwareInterface;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

abstract class SoapController extends SoapGetController implements
    FormAwareInterface,
    FormHandlerAwareInterface,
    SoapApiCrudInterface
{
    /**
     * {@inheritdoc}
     */
    public function handleUpdateRequest($id)
    {
        return null !== $this->processForm($this->getEntity($id));
    }

    /**
     * Create new
     *
     * @param mixed $_ [optional] Arguments will be passed to createEntity method
     * @return integer
     */
    public function handleCreateRequest()
    {
        $entity = call_user_func_array(array($this, 'createEntity'), func_get_args());
        $entity = $this->processForm($entity);

        return $this->getManager()->getEntityId($entity);
    }

    /**
     * Create new entity
     *
     * @param mixed $_ [optional] Arguments will be passed to createEntity method of manager (result of getManager)
     * @return mixed
     */
    protected function createEntity()
    {
        return call_user_func_array(array($this->getManager(), 'createEntity'), func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function handleDeleteRequest($id)
    {
        try {
            $this->getDeleteHandler()->handleDelete($id, $this->getManager());
        } catch (EntityNotFoundException $notFoundEx) {
            throw new \SoapFault('NOT_FOUND', sprintf('Record with ID "%s" can not be found', $id));
        } catch (ForbiddenException $forbiddenEx) {
            throw new \SoapFault('FORBIDDEN', $forbiddenEx->getMessage());
        }

        return true;
    }

    /**
     * Form processing
     *
     * @param  mixed $entity Entity object
     *
     * @return mixed The instance of saved entity
     *
     * @throws \SoapFault
     */
    protected function processForm($entity)
    {
        $this->fixRequestAttributes($entity);

        $result = $this->getFormHandler()->process($entity);
        if (is_object($result)) {
            return $result;
        } elseif (true === $result) {
            // some form handlers may return true/false rather that saved entity
            return $entity;
        }

        throw new \SoapFault('BAD_REQUEST', $this->getFormErrors($this->getForm()));
    }

    /**
     * @param  FormInterface $form
     * @return string        All form's error messages concatenated into one string
     */
    protected function getFormErrors(FormInterface $form)
    {
        $errors = '';

        /** @var FormError $error */
        foreach ($form->getErrors() as $error) {
            $errors .= $error->getMessage() ."\n";
        }

        foreach ($form->all() as $key => $child) {
            if ($err = $this->getFormErrors($child)) {
                $errors .= sprintf("%s: %s\n", $key, $err);
            }
        }

        return $errors;
    }

    /**
     * Convert SOAP request to format applicable for form.
     *
     * @param object $entity
     */
    protected function fixRequestAttributes($entity)
    {
        $request = $this->container->get('request');
        $entityData = $request->get($this->getForm()->getName());
        if (!is_object($entityData)) {
            return;
        }

        $data = $this->convertValueToArray($entityData);
        $this->fixFormData($data, $entity);
        $request->request->set($this->getForm()->getName(), $data);
    }

    /**
     * Fixes form data
     *
     * @param array $data
     * @param mixed $entity
     * @return bool true if any changes in $data array was made; otherwise, false.
     */
    protected function fixFormData(array &$data, $entity)
    {
        return false;
    }

    /**
     * Gets an object responsible to delete an entity.
     *
     * @return DeleteHandler
     */
    protected function getDeleteHandler()
    {
        return $this->container->get('oro_soap.handler.delete');
    }

    /**
     * Converts entity data to array
     *
     * @param mixed $value
     * @return array
     */
    protected function convertValueToArray($value)
    {
        // special case for ordered arrays
        if (is_object($value)) {
            if ($value instanceof \stdClass && isset($value->item) && is_array($value->item)) {
                $value = $value->item;
            } elseif ($value instanceof \DateTime) {
                $value = $value->format(\DateTime::ISO8601);
            } elseif ($value instanceof Collection) {
                $value = $value->toArray();
            } else {
                $value = (array)$value;
            }
        }

        if (is_array($value)) {
            $convertedValue = array();
            foreach ($value as $key => $item) {
                $itemValue = $this->convertValueToArray($item);
                if (!is_null($itemValue)) {
                    $convertedValue[preg_replace('/[^\w+]+/i', '', $key)] = $itemValue;
                }
            }
            $value = $convertedValue;
        }

        return $value;
    }
}
