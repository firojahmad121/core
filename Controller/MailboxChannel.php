<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\Form\FormError;
use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Entity\Mailbox;
use Webkul\UVDesk\CoreBundle\Form\Mailbox as MailboxForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannel extends Controller
{
    public function listMailboxCollection(Request $request)
    {
        $mailbox = new Mailbox();
        $form = $this->createForm(MailboxForm::class, $mailbox);
            
        $mailboxRepository = $this->getDoctrine()->getManager()->getRepository('UVDeskCoreBundle:Mailbox');
        $mailboxCollection = array_map(function($mailbox) {
            return [
                'name' => $mailbox->getName(),
                'email' => $mailbox->getEmail(),
                'mailboxEmail' => $mailbox->getMailboxEmail(),
                'isEnabled' => $mailbox->getIsEnabled(),
                'isLocalized' => $mailbox->getIsLocalized(),
            ];
        }, $mailboxRepository->findAll());

        return $this->render('@UVDeskCore//mailboxList.html.twig', [
            'form' => $form->createView(),
            'mailboxes' => json_encode($mailboxCollection)
        ]);
    }
}
