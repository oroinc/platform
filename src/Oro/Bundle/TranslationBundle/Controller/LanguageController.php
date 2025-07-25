<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Displays the datagrid with languages.
 */
class LanguageController extends AbstractController
{
    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_translation_language_index')]
    #[Template]
    #[AclAncestor('oro_translation_language_view')]
    public function indexAction()
    {
        return [
            'entity_class' => Language::class
        ];
    }
}
