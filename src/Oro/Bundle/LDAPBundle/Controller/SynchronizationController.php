<?php

namespace Oro\Bundle\LDAPBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\LDAPBundle\Manager\ExportManager;
use Oro\Bundle\LDAPBundle\Manager\ImportManager;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/synchronization")
 */
class SynchronizationController extends Controller
{
    /**
     * @Route("/import", name="oro_ldap_import_users")
     * @Template
     * @AclAncestor("oro_ldap_import_users")
     */
    public function importUsersAction(Request $request)
    {
        return $this->getImportManager()->import(!$request->isMethod('POST'));
    }

    /**
     * @Route("/export", name="oro_ldap_export_users")
     * @Template
     * @AclAncestor("oro_ldap_export_users")
     */
    public function exportUsersAction(Request $request)
    {
        return $this->getExportManager()->export(!$request->isMethod('POST'));
    }

    /**
     * @return ImportManager
     */
    protected function getImportManager()
    {
        return $this->container->get('oro_ldap.manager.import');
    }

    /**
     * @return ExportManager
     */
    protected function getExportManager()
    {
        return $this->container->get('oro_ldap.manager.export');
    }
}
