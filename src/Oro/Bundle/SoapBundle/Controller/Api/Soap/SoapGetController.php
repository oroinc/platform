<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Soap;

use Symfony\Component\DependencyInjection\ContainerAware;

use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

abstract class SoapGetController extends ContainerAware implements EntityManagerAwareInterface, SoapApiReadInterface
{
    /**
     * {@inheritDoc}
     */
    public function handleGetListRequest($page = 1, $limit = 10)
    {
        return $this->transformToSoapEntities($this->getManager()->getList($limit, $page));
    }

    /**
     * {@inheritDoc}
     */
    public function handleGetRequest($id)
    {
        return $this->transformToSoapEntity($this->getEntity($id));
    }

    /**
     * Get entity by identifier.
     *
     * @param  mixed      $id
     * @return object
     * @throws \SoapFault
     */
    protected function getEntity($id)
    {
        $entity = $this->getManager()->find($id);

        if (!$entity) {
            throw new \SoapFault('NOT_FOUND', sprintf('Record #%u can not be found', $id));
        }

        return $entity;
    }

    /**
     * Transform entity to corresponding SOAP class if it exists.
     *
     * @param mixed $entity
     * @return mixed|SoapEntityInterface
     */
    protected function transformToSoapEntity($entity)
    {
        $soapEntityClass = $this->getSoapEntityClass($entity);
        if (class_exists($soapEntityClass) &&
            in_array('Oro\Bundle\SoapBundle\Entity\SoapEntityInterface', class_implements($soapEntityClass))
        ) {
            /** @var SoapEntityInterface $soapEntity */
            $soapEntity = new $soapEntityClass();
            $soapEntity->soapInit($entity);
            return $soapEntity;
        }
        return $entity;
    }

    /**
     * Transform the list of entities to the list of corresponding SOAP classes objects if such class exists.
     *
     * @param mixed $entities
     * @return mixed|SoapEntityInterface
     */
    protected function transformToSoapEntities($entities)
    {
        $result = array();
        foreach ($entities as $key => $entity) {
            $result[$key] = $this->transformToSoapEntity($entity);
        }
        return $result;
    }

    /**
     * Gets SOAP entity class
     *
     * @param object $entity
     * @return string
     */
    protected function getSoapEntityClass($entity)
    {
        return get_class($entity) . 'Soap';
    }
}
