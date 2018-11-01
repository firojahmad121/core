<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Thread extends Controller
{
    public function saveThread($ticketId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $params = $request->request->all();
        $ticket = $entityManager->getRepository('UVDeskCoreBundle:Ticket')->findOneById($ticketId);

        // Validate Request
        if (empty($ticket)) {
            throw new \Exception('Ticket not found', 404);
        } else if ('POST' !== $request->getMethod()) {
            throw new \Exception('Invalid Request', 403);
        } else {
            // Validate user permission if the thread being added is a note
            if ('note' == $params['threadType']) {
                if (false == $this->get('user.service')->isAccessAuthorized('ROLE_AGENT_ADD_NOTE')) {
                    throw new \Exception('Insufficient Permisions', 400);
                }
            }

            // // Deny access unles granted ticket view permission
            // $this->denyAccessUnlessGranted('AGENT_VIEW', $ticket);

            // Check if reply content is empty
            $parsedMessage = trim(strip_tags($params['reply'], '<img>'));
            $parsedMessage = str_replace('&nbsp;', '', $parsedMessage);
            $parsedMessage = str_replace(' ', '', $parsedMessage);
            
            if (null == $parsedMessage) {
                $this->addFlash('warning', "Reply content cannot be left blank.");
            }

            // @TODO: Validate file attachments
            // if (true !== $this->get('file.service')->validateAttachments($request->files->get('attachments'))) {
            //     $this->addFlash('warning', "Invalid attachments.");
            // }
        }
        
        $threadDetails = [
            'user' => $this->getUser(),
            'createdBy' => 'agent',
            'source' => 'website',
            'threadType' => strtolower($params['threadType']),
            'message' => $params['reply'],
            'attachments' => $request->files->get('attachments')
        ];
        
        if(isset($params['to']))
            $threadDetails['to'] = $params['to'];
        
        // Create Thread
        $thread = $this->get('ticket.service')->createThread($ticket, $threadDetails);
        // $this->addFlash('success', ucwords($params['threadType']) . " added successfully.");

        // @TODO: Remove Agent Draft Thread
        // @TODO: Trigger Thread Created Event
        // dump($thread);
        
        $ticket->createdThread = $thread;

        // Trigger agent reply event
        $event = new GenericEvent('uvdesk.ticket.agent_reply', [
            'entity' => $ticket,
        ]);
      
        $this->get('event_dispatcher')->dispatch('uvdesk.automation.workflow.execute', $event);

        // Check if ticket status needs to be updated
        $updateTicketToStatus = !empty($params['status']) ? (trim($params['status']) ?: null) : null;
       
        if (!empty($updateTicketToStatus) && $this->get('user.service')->isAccessAuthorized('ROLE_AGENT_UPDATE_TICKET_STATUS')) {
            $ticketStatus = $em->getRepository('UVDeskCoreBundle:TicketStatus')->findOneById($updateTicketToStatus);

            if (!empty($ticketStatus) && $ticketStatus->getId() === $ticket->getStatus()->getId()) {
                $ticket->setStatus($ticketStatus);

                $entityManager->persist($ticket);
                $entityManager->flush();

                // @TODO: Trigger Ticket Status Updated Event
            }
        }
       
        // Redirect to either Ticket View | Ticket Listings
        if ('redirect' === $params['nextView']) {
            return $this->redirect($this->generateUrl('helpdesk_member_ticket_collection'));
        }
        
        return $this->redirect($this->generateUrl('helpdesk_member_ticket', ['ticketId' => $ticket->getId()]));
    }
    
    public function updateThreadXhr(Request $request)
    {
        $json = array();
        $content = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        switch($request->getMethod())
        {
            case "PUT":
                if(str_replace(' ','',str_replace('&nbsp;','',trim(strip_tags($content['reply'], '<img>')))) != "") 
                {
                    $thread = $em->getRepository('UVDeskCoreBundle:Thread')->find($content['id']);
                    
                    $thread->setMessage($content['reply']);            
                    $em->persist($thread);
                    $em->flush();
                    $ticket = $thread->getTicket();
                    $ticket->currentThread = $thread;
                  
                    $json['alertMessage'] = 'Success ! Thread updated successfully.';
                    $json['alertClass'] = 'success';
                } else {
                    $json['alertMessage'] = 'Error ! Reply field can not be blank.';
                    $json['alertClass'] = 'danger';
                }
            break;
            case "DELETE":
                $thread = $em->getRepository('UVDeskCoreBundle:Thread')->findOneBy(array('id' => $request->get('threadId'), 'ticket' => $content['ticketId']));
                if($thread) {
                  
                    $em->remove($thread);
                    $em->flush();
                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = 'Success ! Thread removed successfully.';
                } else {
                    $json['alertClass'] = 'danger';
                    $json['alertMessage'] = 'Error ! Invalid thread.';
                }
            break;
            case "PATCH":
                $thread = $em->getRepository('UVDeskCoreBundle:Thread')->findOneBy(array('id' => $request->get('threadId'), 'ticket' => $content['ticketId']));
                if($thread)
                {
                    if($content['updateType'] == 'lock')
                    {
                        $thread->setIsLocked($content['isLocked']);
                        $em->persist($thread);
                        $em->flush();
                        if($content['isLocked'])
                            $json['alertMessage'] = 'Success ! Thread locked successfully.';
                        else
                            $json['alertMessage'] = 'Success ! Thread unlocked successfully.';
                        $json['alertClass'] = 'success';
                        
                    } elseif($content['updateType'] == 'bookmark') {
                        
                        $thread->setIsBookmarked($content['bookmark']);
                        $em->persist($thread);
                        $em->flush();
                        if($content['bookmark'])
                            $json['alertMessage'] = 'Success ! Thread pinned successfully.';
                        else
                            $json['alertMessage'] = 'Success ! unpinned removed successfully.';
                        $json['alertClass'] = 'success';
                    }
                } else {
                    $json['alertClass'] = 'danger';
                    $json['alertMessage'] = 'Error ! Invalid thread.';
                }
            break;
            default:
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Error ! Invalid Request.';
            break;

        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
}
