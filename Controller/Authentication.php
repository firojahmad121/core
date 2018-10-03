<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Authentication extends Controller
{
    public function login(Request $request)
    {
        if (null == $this->get('user.service')->getSessionUser()) {
            return $this->render('@UVDeskCore//login.html.twig', [
                'last_username' => $this->get('security.authentication_utils')->getLastUsername(),
                'error' => $this->get('security.authentication_utils')->getLastAuthenticationError(),
            ]);
        }
        
        return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
    }

    public function logout(Request $request)
    {
        return;
    }

    public function forgotPassword(Request $request)
    {
        dump($request);
        die;
    }
}
