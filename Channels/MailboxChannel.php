<?php

namespace Webkul\UVDesk\CoreBundle\Channels;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannel extends Controller
{
    public function listMailboxCollection(Request $request) 
    {
        if (!$this->get('user.service')->checkPermission('ROLE_ADMIN')) {          
           return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $mailboxCollection = array_map(function ($mailbox) {
            return [
                'id' => $mailbox->getId(),
                'name' => $mailbox->getName(),
                'email' => $mailbox->getEmail(),
                'isEnabled' => $mailbox->getIsEnabled(),
                'isLocalized' => $mailbox->getIsLocalized(),
                'mailboxEmail' => $mailbox->getMailboxEmail(),
            ];
        }, $this->getDoctrine()->getManager()->getRepository('UVDeskCoreBundle:Mailbox')->findAll());

        return $this->render('@UVDeskCore//mailboxList.html.twig', [
            'mailboxes' => json_encode($mailboxCollection),
        ]);
    }
}
