<?php

namespace Oro\Bundle\NoteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\NoteBundle\Entity\Note;

/**
 * @Route("/note")
 */
class NoteController extends Controller
{
    /**
     * @Route(
     *      ".{_format}",
     *      name="oro_note_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_note_view",
     *      type="entity",
     *      class="OroNoteBundle:Note",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/view/{id}", name="oro_note_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_note_view")
     * @Template
     */
    public function viewAction(Note $note)
    {
        return array('entity' => $note);
    }

    /**
     * @Route("/update/{id}", name="oro_note_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_note_update",
     *      type="entity",
     *      class="OroNoteBundle:Note",
     *      permission="EDIT"
     * )
     */
    public function updateAction(Note $note)
    {
        return $this->update($note);
    }

    /**
     * @param Note $note
     * @return array
     */
    protected function update(Note $note)
    {
    }
}
