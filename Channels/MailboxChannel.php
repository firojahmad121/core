<?php

namespace Webkul\UVDesk\CoreBundle\Channels;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query;

class MailboxChannel extends Controller
{
    public function listMailboxCollection(Request $request) 
    {
        if(!$this->get('user.service')->checkPermission('ROLE_ADMIN')){          
           return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
           exit;
        }
        $query = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Mailbox')->createQueryBuilder('mailbox')->getQuery();   
        return $this->render('@UVDeskCore//mailboxList.html.twig',['mailboxes' => json_encode($query->getREsult(Query::HYDRATE_ARRAY))]);
    }
}
