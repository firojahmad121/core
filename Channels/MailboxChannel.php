<?php

namespace Webkul\UVDesk\CoreBundle\Channels;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannel extends Controller
{
    public function listMailboxCollection(Request $request) 
    {
        $entityManager = $this->getDoctrine()->getManager();
        $mailboxCollection = $entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findAll();
        
        return $this->render('@UVDeskCore//mailboxList.html.twig', [
            'mailboxes' => json_encode($mailboxCollection),
        ]);
    }
}
