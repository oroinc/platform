UPGRADE FROM 2.1 to 2.2
=======================

ActivityBundle
--------------
- Class `Oro\Bundle\ActivityBundle\Provider\ContextGridProvider` was removed
- Class `Oro\Bundle\ActivityBundle\Controller\ActivityController`
    - action `contextAction` is rendered in `OroDataGridBundle:Grid/dialog:multi.html.twig`
    - action `contextGridAction` was removed

DataGridBundle
--------------
- Interface `Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface`
    - the signature of method `getDatagrid` was changed - added new parameter `array $additionalParameters = []`.

