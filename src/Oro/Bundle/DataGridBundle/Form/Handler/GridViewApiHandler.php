<?php

namespace Oro\Bundle\DataGridBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class GridViewApiHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var Registry */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param FormInterface         $form
     * @param Request               $request
     * @param Registry              $registry
     * @param AclHelper             $aclHelper
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        Registry $registry,
        AclHelper $aclHelper,
        TokenStorageInterface $tokenStorage
    ) {
        $this->form         = $form;
        $this->request      = $request;
        $this->registry     = $registry;
        $this->aclHelper    = $aclHelper;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param GridView $entity
     *
     * @return boolean
     */
    public function process(GridView $entity)
    {
        $entity->setFiltersData();
        $entity->setSortersData();
        $entity->setColumnsData();

        $this->form->setData($entity);
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
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
        $default = $this->form->get('is_default')->getData();
        $this->setDefaultGridView($entity, $default);

        $this->fixFilters($entity);
        $om = $this->registry->getManagerForClass('OroDataGridBundle:GridView');
        $om->persist($entity);
        $om->flush();
    }

    /**
     * @param GridView $gridView
     * @param bool     $default
     */
    protected function setDefaultGridView(GridView $gridView, $default)
    {
        $user              = $this->tokenStorage->getToken()->getUser();
        $isGridViewDefault = $gridView->getUsers()->contains($user);
        // Checks if default grid view changed
        if ($isGridViewDefault !== $default) {
            $om = $this->registry->getManagerForClass('OroDataGridBundle:GridView');
            /** @var GridViewRepository $repository */
            $repository = $om->getRepository('OroDataGridBundle:GridView');
            $gridViews  = $repository->findDefaultGridViews($this->aclHelper, $user, $gridView, false);
            foreach ($gridViews as $view) {
                $view->removeUser($user);
            }

            if ($default) {
                $gridView->addUser($user);
            }
        }
    }

    /**
     * @todo Remove once https://github.com/symfony/symfony/issues/5906 is fixed
     *
     * @param GridView $gridView
     */
    protected function fixFilters(GridView $gridView)
    {
        $filters = $gridView->getFiltersData();
        foreach ($filters as $name => $filter) {
            if (array_key_exists('type', $filter) && $filter['type'] == null) {
                $filters[$name]['type'] = '';
            }
        }

        $gridView->setFiltersData($filters);
    }
}
