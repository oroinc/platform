<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ImportExportBundle\Controller\ImportExportController as BaseController;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Form\Type\TranslationImportType;

class ImportExportController extends BaseController
{
    /**
     * @Route("/translation-import", name="oro_translation_import_form")
     * @AclAncestor("oro_importexport_import")
     * @Template
     *
     * @param Request $request
     *
     * @return array
     */
    public function importFormAction(Request $request)
    {
        return parent::importFormAction($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function getImportForm($entityName)
    {
        return $this->createForm(TranslationImportType::NAME, null, ['entityName' => $entityName]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExportHandler()
    {
        return $this->get('oro_translation.importexport.handler.export')->setLanguage($this->getLanguage());
    }

    /**
     * @return Language
     */
    protected function getLanguage()
    {
        $options = $this->getOptionsFromRequest();
        if (isset($options['language_id']) && ($options['language_id'] > 0)) {
            return $this->get('oro_entity.doctrine_helper')->getEntityReference(
                Language::class,
                $options['language_id']
            );
        }

        return null;
    }
}
