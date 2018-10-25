<?php

namespace Webkul\UVDesk\CoreBundle\Channels;

use Symfony\Component\Form\FormError;
use Webkul\UVDesk\SupportBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Entity\Mailbox;
use Webkul\UVDesk\CoreBundle\Form\Mailbox as MailboxForm;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannelXHR extends Controller
{

  
    public function processMailXHR(Request $request)
    {

        if(!$this->get('user.service')->checkPermission('ROLE_ADMIN')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
         }

        if ("POST" == $request->getMethod() && null != $request->get('message')) {
            $message = $request->get('message');
            $this->get('uvdesk.core.mailbox')->processMail($message);
        }
        
        return new Response(null, 200, ['Content-Type' => 'application/json']);
    }

    public function verifyEmailForwardingXHR(Request $request)
    {
        if(!$this->get('user.service')->checkPermission('ROLE_ADMIN')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
         }

        $json = array();
        if($request->getMethod() == "PUT") {
            $content = json_decode($request->getContent(), true);

            $entityManager = $this->getDoctrine()->getManager();
            $mailbox = $entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findOneBy(array('id' => $content['id']));

            if(!$content['data']['flag']) {
                $mailbox->setIsEnabled(1);
                $entityManager->persist($mailbox);
                $entityManager->flush();
                $text = 'This is test email , if you see this email in your ticket list pannel that means auto forwarding has been enabled successfully.';
                $data = array(
                    'email' => $mailbox->getEmail(),
                    'subject' => 'Webkul Helpdesk Test Email',
                    'message' => $text,
                    'references' => 'mailbox-'.$mailbox->getId(),
                    'replyTo' => $mailbox->getMailboxEmail()
                );
            
                $this->container->get('workflow.service')->sendMail($data,$mailbox);
            }
            if($mailbox->getIsEnabled()) {
                // $json['mailbox'] = json_decode($this->get('email.service')->objectSerializer($mailbox,$ignoredFields));
                $json['alertClass'] = 'success';
                $json['isActive'] = true;
            } else {
                $json['alertClass'] = 'danger';
                $json['isActive'] = false;
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function updateMailboxChannelXHR($mailboxId)
    {

        if(!$this->get('user.service')->checkPermission('ROLE_ADMIN')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
         }

        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $requestParams = json_decode($request->getContent(), true) ?: $request->request->all();
        switch (strtoupper($request->getMethod())) {
            case 'POST':
                if (empty($requestParams['email']) || empty($requestParams['name'])) {
                    return new Response(json_encode([
                        'alertClass' => 'danger',
                        'alertMessage' => 'Missing fields'
                    ]), 403, ['Content-Type' => 'application/json']);
                }

                $mailbox = new Mailbox();
                $mailboxChannelForm = $this->createForm(MailboxForm::class, $mailbox);
                $mailboxChannelForm->submit([
                    'name' => $requestParams['name'],
                    'email' => $requestParams['email'],
                ]);

                $user = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneByEmail($mailbox->getEmail());
                $existingMailbox = $entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findOneByEmail($mailbox->getEmail());

                if (!empty($user) && $user->getAgentInstance() != null) {
                    // An agent account exists with the specified email address
                    $responseContent = [
                        'alertClass' => 'danger',
                        'alertMessage' => 'An agent account exists with the specified email address',
                    ];

                    return new Response(json_encode($responseContent), 400, ['Content-Type' => 'application/json']);
                } else if (!empty($existingMailbox)) {
                    // A mailbox has already been created with the specified email address
                    $responseContent = [
                        'alertClass' => 'danger',
                        'alertMessage' => 'A mailbox has already been created with the specified email address',
                    ];

                    return new Response(json_encode($responseContent), 400, ['Content-Type' => 'application/json']);
                }

                // $mailboxChannelForm->handleRequest($request);
                $mailbox->setIsEnabled(false);
                $mailbox->setIsLocalized(false);
                $mailbox->setMailboxEmail($this->get('uvdesk.core.mailbox')->getRandomizedMailboxEmail());

                $entityManager->persist($mailbox);
                $entityManager->flush();
                
                return new Response(json_encode([
                    'alertClass' => 'success',
                    'alertMessage' => 'Mailbox saved successfully',
                    'allowedToAdd' => true,
                    'existingUserEmail' => false,
                    'mailbox' => [
                        'id' => $mailbox->getId(),
                        'name' => $mailbox->getName(),
                        'email' => $mailbox->getEmail(),
                        'mailboxEmail' => $mailbox->getMailboxEmail(),
                        'isEnabled' => $mailbox->getIsEnabled(),
                        'isLocalized' => $mailbox->getIsLocalized(),
                    ],
                ]), 200, ['Content-Type' => 'application/json']);
                break;
            case 'PUT':
                if (empty($requestParams['email']) || empty($requestParams['name'])) {
                    return new Response(json_encode([
                        'alertClass' => 'danger',
                        'alertMessage' => 'Missing fields'
                    ]), 403, ['Content-Type' => 'application/json']);
                } else {
                    $mailbox = $entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findOneById($mailboxId);

                    if (empty($mailbox)) {
                        return new Response(json_encode([
                            'alertClass' => 'danger',
                            'alertMessage' => 'Mailbox not found'
                        ]), 404, ['Content-Type' => 'application/json']);
                    }
                }

              
                $mailboxChannelForm = $this->createForm(MailboxForm::class, $mailbox);
                $mailboxChannelForm->submit([
                    'name' => $requestParams['name'],
                    'email' => $requestParams['email'],
                ]);

                $user = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneByEmail($mailbox->getEmail());
                $existingMailbox = $entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findOneByEmail($mailbox->getEmail());
             
                $entityManager->persist($mailbox);
                $entityManager->flush();                
                return new Response(json_encode([
                    'alertClass' => 'success',
                    'alertMessage' => 'Mailbox updated successfully',
                ]), 200, ['Content-Type' => 'application/json']);
                break;
            case 'DELETE':
                $mailbox = $entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findOneById($mailboxId);

                if (!empty($mailbox)) {
                    $responseContent = [
                        'alertClass' => 'success',
                        'alertMessage' => 'Mailbox removed successfully',
                    ];

                    $entityManager->remove($mailbox);
                    $entityManager->flush();
                } else {
                    $responseContent = [
                        'alertClass' => 'warning',
                        'alertMessage' => 'Mailbox not found',
                    ];
                }

                return new Response(json_encode($responseContent), 200, ['Content-Type' => 'application/json']);
                break;
            default:
                break;
        }

        return new Response(null, 404, ['Content-Type' => 'application/json']);
    }
    
    public function getRandomMailboxId() {
        $length = 20;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $email = 'support.'.$randomString."@uvdesk.com";

        $em = $this->getDoctrine()->getManager();
        $mailbox = $em->getRepository('UVDeskCoreBundle:Mailbox')->findOneBy(array('mailboxEmail' => $email));
        if($mailbox)
            $this->getRandomMailboxId();
        return $email;
    }
}
