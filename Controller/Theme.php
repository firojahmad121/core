<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Theme extends Controller
{
    public function updateHelpdeskTheme(Request $request) {
        if($request->getMethod() == "POST") {
            $values = $request->request->all();
            $em = $this->getDoctrine()->getManager();

            $websiteRepo = $em->getRepository('UVDeskCoreBundle:Website');
            $website = $websiteRepo->findOneBy(['code' => 'helpdesk']);

            $website->setThemeColor($values['themeColor']);
            $em->persist($website);
            $em->flush();
        }

        return $this->render('@UVDeskCore/theme.html.twig');
    }
}