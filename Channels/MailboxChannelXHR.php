<?php

namespace Webkul\UVDesk\CoreBundle\Channels;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannelXHR extends Controller
{
    public function processMailXHR(Request $request)
    {
        // Return HTTP_OK Response
        // $response = new Response(Response::HTTP_OK);
        // $response->send();

        if ("POST" == $request->getMethod() && null != $request->get('email')) {
            $message = $request->get('email');

            $this->get('logger')->info('New Email Received');
            $this->get('logger')->info($message);
            
            $this->get('uvdesk.core.mailbox')->processMail($message);
        }
        
        exit(0);
    }
}
