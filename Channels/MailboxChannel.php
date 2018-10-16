<?php

namespace Webkul\UVDesk\CoreBundle\Channels;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Form as CoreBundleForms;
use Webkul\UVDesk\CoreBundle\Entity as CoreBundleEntities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannel extends Controller
{
    public function listMailboxCollection(Request $request) 
    {
        $entityManager = $this->getDoctrine()->getManager();
        $mailboxCollection = $entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findAll();
        $mailboxForm = $this->createForm(CoreBundleForms\Mailbox::class, new CoreBundleEntities\Mailbox(), [
            'validation_groups' => ['Mailbox'],
        ]);

        return $this->render('@UVDeskCore//mailboxList.html.twig', [
            'form' => $mailboxForm->createView(),
            // 'list_items' => $this->getListItems($request),
            // 'information_items' => $this->getRightSidebarInfoItems($request),
            'mailboxes' => json_encode($mailboxCollection),
        ]);
    }
}
