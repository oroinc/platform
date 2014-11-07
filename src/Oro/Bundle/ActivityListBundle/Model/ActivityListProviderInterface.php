<?php

namespace Oro\Bundle\ActivityListBundle\Model;


interface ActivityListProviderInterface
{
    /**
     * return pairs of class name and id,
     *
     * @return array
     */
    public function getTargets();

    /**
     * returns a class name of entity for which we monitor changes
     *
     * @return string
     */
    public function getActivityClass();

    /**
     * @param object $entity
     *
     * @return string
     */
    public function getSubject($entity);

//    /**
//     * Should return User, the one who made changes (create/update) to activity instance
//     *
//     * @param object $entity
//     *
//     * @return mixed
//     */
//    public function getActor($entity);

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getData($entity);

    /**
     * @return string
     */
    public function getTemplate();

    /**
     * Should return array of route names as key => value
     * e.g. [
     *      'itemView'  => 'item_view_route',
     *      'itemEdit'  => 'item_edit_route',
     *      'itemDelete => 'item_delete_route'
     * ]
     *
     * @return array
     */
    public function getRoutes();

    /**
     * @param $entity
     *
     * @return integer
     */
    public function getActivityId($entity);

    /**
     * Check if provider supports given entity
     *
     * @param object|string $entity
     *
     * @return bool
     */
    public function isApplicable($entity);
}
