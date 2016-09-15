<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;

class TranslationController extends BaseController
{
    /**
     * @Route("/", name="oro_translation_translation_index")
     * @Template
     * @Acl(
     *      id="oro_translation_language_view",
     *      type="entity",
     *      class="OroTranslationBundle:Language",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_translation.entity.language.class')
        ];
    }
}
