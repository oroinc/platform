<?php

namespace Oro\Bundle\DataGridBundle\Form\Handler;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;

class GridViewApiHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $om
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $om)
    {
        $this->form = $form;
        $this->request = $request;
        $this->om = $om;
    }

    /**
     * @param GridView $entity
     *
     * @return boolean
     */
    public function process(GridView $entity)
    {
        $this->form->setData($entity);
        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $data = $this->request->request->all();
            unset($data['name']);
            if ($this->form->has('owner')) {
                $data['owner'] = $entity->getOwner();
            }
            $this->form->submit($data);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * @param GridView $entity
     */
    protected function onSuccess(GridView $entity)
    {
        $this->om->persist($entity);
        $this->om->flush();
    }
}
