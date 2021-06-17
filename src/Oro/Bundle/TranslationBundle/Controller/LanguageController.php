<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Displays the datagrid with languages.
 */
class LanguageController extends AbstractController
{
    /**
     * @Route("/", name="oro_translation_language_index")
     * @Template
     * @AclAncestor("oro_translation_language_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Language::class
        ];
    }
}
