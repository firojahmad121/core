<?php

namespace Webkul\UVDesk\CoreBundle\Channels;

use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\Response;
// use Webkul\UVDesk\CoreBundle\Form as CoreBundleForms;
// use Webkul\UVDesk\CoreBundle\Entity as CoreBundleEntities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query;

class MailboxChannel extends Controller
{
    public function listMailboxCollection(Request $request) 
    {
        $query = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Mailbox')->createQueryBuilder('mailbox')->getQuery();   
        return $this->render('@UVDeskCore//mailboxList.html.twig',['mailboxes' => json_encode($query->getREsult(Query::HYDRATE_ARRAY))]);
    }
}
