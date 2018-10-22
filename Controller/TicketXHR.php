<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Entity\SupportLabel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TicketXHR extends Controller
{
    public function loadTicketXHR($ticketId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        dump($request);
        die;
    }

    public function bookmarkTicketXHR($ticketId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $requestContent = json_decode($request->getContent(), true);
        $ticket = $entityManager->getRepository('UVDeskCoreBundle:Ticket')->findOneById($requestContent['id']);

        if (!empty($ticket)) {
            $ticket->setIsStarred(!$ticket->getIsStarred());

            $entityManager->persist($ticket);
            $entityManager->flush();

            return new Response(json_encode(['alertClass' => 'success']), 200, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function ticketLabelXHR(Request $request)
    {
        $method = $request->getMethod();
        $content = $request->getContent();
        $em = $this->getDoctrine()->getManager();

        if($method == "POST") {
            $data = json_decode($content, true);
            if($data['name'] != "") {
                $label = new SupportLabel();
                $label->setName($data['name']);
                if(isset($data['color']))
                    $label->setColorCode($data['color']);
                $label->setUser($this->get('user.service')->getCurrentUser());
                $em->persist($label);
                $em->flush();

                $json['alertClass'] = 'success';
                $json['alertMessage'] = 'Success ! Label created successfully.';
                $json['label'] = json_encode([
                    'id' => $label->getId(),
                    'name' => $label->getName(),
                    'color' => $label->getColorCode(),
                    'labelUser' => $label->getUser()->getId(),
                ]);
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Error ! Label name can not be blank.';
            }
        } elseif($method == "PUT") {
            $data = json_decode($content, true);
            $label = $em->getRepository('UVDeskCoreBundle:SupportLabel')->findOneBy(array('id' => $request->attributes->get('ticketLabelId')));
            if($label) {
                $label->setName($data['name']);
                if(!empty($data['color'])) {
                    $label->setColorCode($data['color']);
                }
                $em->persist($label);
                $em->flush();

                $json['label'] = json_encode([
                    'id' => $label->getId(),
                    'name' => $label->getName(),
                    'color' => $label->getColorCode(),
                    'labelUser' => $label->getUser()->getId(),
                ]);
                $json['alertClass'] = 'success';
                $json['alertMessage'] = 'Success ! Label updated successfully.';
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Error ! Invalid label id.';
            }
        } elseif($method == "DELETE") {
            $label = $em->getRepository('UVDeskCoreBundle:SupportLabel')->findOneBy(array('id' => $request->attributes->get('ticketLabelId')));
            if($label) {
                $em->remove($label);
                $em->flush();
                $json['alertClass'] = 'success';
                $json['alertMessage'] = 'Success ! Label removed successfully.';
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = 'Error ! Invalid label id.';
            }
        }

        return new Response(json_encode($json), 200, ['Content-Type' => 'application/json']);
    }

    public function updateTicketDetails()
    {
        $response = $this->isAuthorized('ROLE_AGENT_EDIT_TICKET');

        $ticketId = $request->attributes->get('id');
        $em = $this->getDoctrine()->getManager();
        $ticket = $em->getRepository('UVDeskCoreBundle:Ticket')->find($ticketId);
        if(!$ticket)
            $this->noResultFound();
        $this->denyAccessUnlessGranted('VIEW', $ticket);

        $error = false;
        $message = '';
        if($request->request->get('subject') == '') {
            $error = true;
            $message = $this->get('translator')->trans("Error! Subject field is mandatory");
        } elseif($request->request->get('reply') == '') {
            $error = true;
            $message = $this->get('translator')->trans("Error! Reply field is mandatory");
        }

        if(!$error) {
            $ticket->setSubject($request->request->get('subject'));
            $createThread = $this->get('ticket.service')->getCreateReply($ticket->getId(), false);
            $createThread = $em->getRepository('UVDeskCoreBundle:Thread')->find($createThread['id']);
            $createThread->setReply($request->request->get('reply'));

            $em->persist($createThread);
            $em->persist($ticket);
            $em->flush();

            $this->get('event.manager')->trigger([
                    'event' => 'ticket.updated',
                    'entity' => $ticket,
                ]);

            $request->getSession()->getFlashBag()->set('success',  $this->get('translator')->trans('Success ! Ticket has been updated successfully.'));
        } else {
            $this->addFlash(
                'warning',
                $message
            );
        }

        return $this->redirect($this->generateUrl('view_ticket', [
                'id' => $ticket->getIncrementId()
            ]));
    }

    public function updateTicketAttributes($ticketId)
    {
        // @TODO: Ticket Voter
        // $this->denyAccessUnlessGranted('VIEW', $ticket);
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $requestContent = $request->request->all() ?: json_decode($request->getContent(), true);
        $ticket = $entityManager->getRepository('UVDeskCoreBundle:Ticket')->findOneById($ticketId);
        
        // Validate request integrity
        if (empty($ticket)) {
            $responseContent = [
                'alertClass' => 'danger',
                'alertMessage' => "Unable to retrieve details for ticket #$ticketId.",
            ];

            return new Response(json_encode($responseContent), 200, ['Content-Type' => 'application/json']);
        } else if (!isset($requestContent['attribute']) || !isset($requestContent['value'])) {
            $responseContent = [
                'alertClass' => 'danger',
                'alertMessage' => "Insufficient details provided.",
            ];

            return new Response(json_encode($responseContent), 400, ['Content-Type' => 'application/json']);
        }

        // Update attribute
        switch ($requestContent['attribute']) {
            case 'agent':
                // $this->isAuthorized('ROLE_AGENT_ASSIGN_TICKET');
                $agent = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneById($requestContent['value']);

                if (empty($agent)) {
                    // User does not exist
                    return new Response(json_encode([
                        'alertClass' => 'danger',
                        'alertMessage' => 'Unable to retrieve agent details',
                    ]), 404, ['Content-Type' => 'application/json']);
                } else {
                    // Check if an agent instance exists for the user
                    $agentInstance = $agent->getAgentInstance();

                    if (empty($agentInstance)) {
                        // Agent does not exist
                        return new Response(json_encode([
                            'alertClass' => 'danger',
                            'alertMessage' => 'Unable to retrieve agent details',
                        ]), 404, ['Content-Type' => 'application/json']);
                    }
                }

                $agentDetails = $agentInstance->getPartialDetails();

                // Check if ticket is already assigned to the agent
                if ($ticket->getAgent() && $agent->getId() === $ticket->getAgent()->getId()) {
                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket already assigned to ' . $agentDetails['name'],
                    ]), 200, ['Content-Type' => 'application/json']);
                } else {
                    $ticket->setAgent($agent);
                    
                    $entityManager->persist($ticket);
                    $entityManager->flush();

                    // @TODO: Trigger ticket agent update event
                    // $notePlaceholders = $this->get('ticket.service')->getNotePlaceholderValues($currentAgent,$targetAgent,'agent');
                    // $this->get('event.manager')->trigger([
                    //     'event' => 'ticket.agent.updated',
                    //     'entity' => $ticket,
                    //     'targetEntity' => $agent,
                    //     'notePlaceholders' => $notePlaceholders
                    // ]);
                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket successfully assigned to ' . $agentDetails['name'],
                    ]), 200, ['Content-Type' => 'application/json']);
                }
                break;
            case 'status':
                // $this->isAuthorized('ROLE_AGENT_UPDATE_TICKET_STATUS');
                $ticketStatus = $entityManager->getRepository('UVDeskCoreBundle:TicketStatus')->findOneById((int) $requestContent['value']);

                if (empty($ticketStatus)) {
                    // Selected ticket status does not exist
                    return new Response(json_encode([
                        'alertClass' => 'danger',
                        'alertMessage' => 'Unable to retrieve status details',
                    ]), 404, ['Content-Type' => 'application/json']);
                }

                if ($ticketStatus->getId() === $ticket->getStatus()->getId()) {
                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket status already set to ' . $ticketStatus->getDescription(),
                    ]), 200, ['Content-Type' => 'application/json']);
                } else {
                    $ticket->setStatus($ticketStatus);
                    
                    $entityManager->persist($ticket);
                    $entityManager->flush();
                
                    // @TODO: Trigger ticket status update event
                    // $notePlaceholders = $this->get('ticket.service')->getNotePlaceholderValues($ticket->getStatus()->getName(),$status->getName(),'status');
                    // $this->get('event.manager')->trigger([
                    //     'event' => 'ticket.status.updated',
                    //     'entity' => $ticket,
                    //     'targetEntity' => $status,
                    //     'notePlaceholders' => $notePlaceholders
                    // ]);

                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket status update to ' . $ticketStatus->getDescription(),
                    ]), 200, ['Content-Type' => 'application/json']);
                }
                break;
            case 'priority':
                // $this->isAuthorized('ROLE_AGENT_UPDATE_TICKET_PRIORITY');
                $ticketPriority = $entityManager->getRepository('UVDeskCoreBundle:TicketPriority')->findOneById($requestContent['value']);

                if (empty($ticketPriority)) {
                    // Selected ticket priority does not exist
                    return new Response(json_encode([
                        'alertClass' => 'danger',
                        'alertMessage' => 'Unable to retrieve priority details',
                    ]), 404, ['Content-Type' => 'application/json']);
                }

                if ($ticketPriority->getId() === $ticket->getPriority()->getId()) {
                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket priority already set to ' . $ticketPriority->getDescription(),
                    ]), 200, ['Content-Type' => 'application/json']);
                } else {
                    $ticket->setPriority($ticketPriority);
                    
                    $entityManager->persist($ticket);
                    $entityManager->flush();

                    // @TODO: Trigger ticket priority update event
                    // $notePlaceholders = $this->get('ticket.service')->getNotePlaceholderValues($ticket->getPriority()->getName(),$priority->getName(),'priority');
                    // $this->get('event.manager')->trigger([
                    //     'event' => 'ticket.priority.updated',
                    //     'entity' => $ticket,
                    //     'targetEntity' => $priority,
                    //     'notePlaceholders' => $notePlaceholders
                    // ]);

                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket priority updated to ' . $ticketPriority->getDescription(),
                    ]), 200, ['Content-Type' => 'application/json']);
                }
                break;
            case 'group':
                // $this->isAuthorized('ROLE_AGENT_ASSIGN_TICKET_GROUP');
                $supportGroup = $entityManager->getRepository('UVDeskCoreBundle:SupportGroup')->findOneById($requestContent['value']);

                if (empty($supportGroup)) {
                    // Selected ticket priority does not exist
                    return new Response(json_encode([
                        'alertClass' => 'danger',
                        'alertMessage' => 'Unable to retrieve support group details',
                    ]), 404, ['Content-Type' => 'application/json']);
                }

                if ($ticket->getSupportGroup() != null && $supportGroup->getId() === $ticket->getSupportGroup()->getId()) {
                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket already assigned to support group ' . $supportGroup->getName(),
                    ]), 200, ['Content-Type' => 'application/json']);
                } else {
                    $ticket->setSupportGroup($supportGroup);

                    $entityManager->persist($ticket);
                    $entityManager->flush();
                    
                    // @TODO: Trigger ticket group update event
                    // $notePlaceholders = $this->get('ticket.service')->getNotePlaceholderValues($ticket->getGroup() ? $ticket->getGroup()->getName() : $this->translate('UnAssigned'),$group ? $group->getName() : $this->translate('UnAssigned'), 'group');
                    // $this->get('event.manager')->trigger([
                    //     'event' => 'ticket.group.updated',
                    //     'entity' => $ticket,
                    //     'targetEntity' => $group,
                    //     'notePlaceholders' => $notePlaceholders
                    // ]);

                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket assigned to support group ' . $supportGroup->getName(),
                    ]), 200, ['Content-Type' => 'application/json']);
                }
                break;
            case 'team':
                // $this->isAuthorized('ROLE_AGENT_ASSIGN_TICKET_GROUP');
                $supportTeam = $entityManager->getRepository('UVDeskCoreBundle:SupportTeam')->findOneById($requestContent['value']);

                if (empty($supportTeam)) {
                    // Selected ticket priority does not exist
                    return new Response(json_encode([
                        'alertClass' => 'danger',
                        'alertMessage' => 'Unable to retrieve support team details',
                    ]), 404, ['Content-Type' => 'application/json']);
                }

                if ($ticket->getSupportTeam() != null && $supportTeam->getId() === $ticket->getSupportTeam()->getId()) {
                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket already assigned to support team ' . $supportTeam->getName(),
                    ]), 200, ['Content-Type' => 'application/json']);
                } else {
                    $ticket->setSupportTeam($supportTeam);

                    $entityManager->persist($ticket);
                    $entityManager->flush();
                    
                    // @TODO: Trigger ticket team update event
                    // $notePlaceholders = $this->get('ticket.service')->getNotePlaceholderValues($ticket->getSubGroup() ? $ticket->getSubGroup()->getName() : $this->translate('UnAssigned'),$team ? $team->getName() : $this->translate('UnAssigned'), 'team');
                    // $this->get('event.manager')->trigger([
                    //     'event' => 'ticket.team.updated',
                    //     'entity' => $ticket,
                    //     'targetEntity' => $team,
                    //     'notePlaceholders' => $notePlaceholders
                    // ]);

                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket assigned to support team ' . $supportTeam->getName(),
                    ]), 200, ['Content-Type' => 'application/json']);
                }
                break;
            case 'type':
                // $this->isAuthorized('ROLE_AGENT_UPDATE_TICKET_TYPE');
                $ticketType = $entityManager->getRepository('UVDeskCoreBundle:TicketType')->findOneById($requestContent['value']);

                if (empty($ticketType)) {
                    // Selected ticket priority does not exist
                    return new Response(json_encode([
                        'alertClass' => 'danger',
                        'alertMessage' => 'Unable to retrieve ticket type details',
                    ]), 404, ['Content-Type' => 'application/json']);
                }

                if ($ticketType->getId() === $ticket->getType()->getId()) {
                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket type already set to ' . $ticketType->getDescription(),
                    ]), 200, ['Content-Type' => 'application/json']);
                } else {
                    $ticket->setType($ticketType);

                    $entityManager->persist($ticket);
                    $entityManager->flush();

                    // @TODO: Trigger ticket type update event
                    // $notePlaceholders = $this->get('ticket.service')->getNotePlaceholderValues($ticket->getType() ? $ticket->getType()->getName() : $this->translate('UnAssigned'),$type->getName(),'type');
                    // $this->get('event.manager')->trigger([
                    //     'event' => 'ticket.type.updated',
                    //     'entity' => $ticket,
                    //     'targetEntity' => $type,
                    //     'notePlaceholders' => $notePlaceholders
                    // ]);

                    return new Response(json_encode([
                        'alertClass' => 'success',
                        'alertMessage' => 'Ticket type updated to ' . $ticketType->getDescription(),
                    ]), 200, ['Content-Type' => 'application/json']);
                }
                break;
            case 'label':
                // $label = $em->getRepository('UVDeskCoreBundle:TicketLabel')->find($data['labelId']);
                // if($label) {
                //     $ticket->removeTicketLabel($label);
                //     $em->persist($ticket);
                //     $em->flush();

                //     $json['alertClass'] = 'success';
                //     $json['alertMessage'] = $this->translate('Success ! Ticket to label removed successfully.');
                // }
                break;
            default:
                break;
        }

        return new Response(json_encode([]), 400, ['Content-Type' => 'application/json']);
    }

    public function listTicketCollectionXHR(Request $request)
    {
        
        if ($request->isXmlHttpRequest()) {
            $paginationResponse = $this->get('ticket.service')->paginateMembersTicketCollection($request);

            return new Response(json_encode($paginationResponse), 200, ['Content-Type' => 'application/json']);
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function updateTicketCollectionXHR(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $massResponse = $this->get('ticket.service')->massXhrUpdate($request);

            return new Response(json_encode($massResponse), 200, ['Content-Type' => 'application/json']);
        }
        return new Response(json_encode([]), 404);
    }
    
    public function loadTicketFilterOptionsXHR(Request $request)
    {
        return new Response(json_encode([]), 404);
    }

    public function saveTicketLabel(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $requestContent = json_decode($request->getContent(), true);
        $ticket = $entityManager->getRepository('UVDeskCoreBundle:Ticket')->findOneById($requestContent['ticketId']);

        if ('POST' == $request->getMethod()) {
            $responseContent = [];
            $user = $this->get('user.service')->getSessionUser();
            $supportLabel = $entityManager->getRepository('UVDeskCoreBundle:SupportLabel')->findOneBy([
                'user' => $user->getId(),
                'name' => $requestContent['name'],
            ]);

            if (empty($supportLabel)) {
                $supportLabel = new SupportLabel();
                $supportLabel->setName($requestContent['name']);
                $supportLabel->setUser($user);

                $entityManager->persist($supportLabel);
                $entityManager->flush();
            }

            $ticketLabelCollection = $ticket->getSupportLabels()->toArray();

            if (empty($ticketLabelCollection)) {
                $ticket->addSupportLabel($supportLabel);
                $entityManager->persist($ticket);
                $entityManager->flush();

                $responseContent['alertClass'] = 'success';
                $responseContent['alertMessage'] = 'Label ' . $supportLabel->getName() . ' added to ticket successfully';
            } else {
                $isLabelAlreadyAdded = false;
                foreach ($ticketLabelCollection as $ticketLabel) {
                    if ($supportLabel->getId() == $ticketLabel->getId()) {
                        $isLabelAlreadyAdded = true;
                        break;
                    }
                }

                if (false == $isLabelAlreadyAdded) {
                    $ticket->addSupportLabel($supportLabel);
                    $entityManager->persist($ticket);
                    $entityManager->flush();

                    $responseContent['alertClass'] = 'success';
                    $responseContent['alertMessage'] = 'Label ' . $supportLabel->getName() . ' added to ticket successfully';
                } else {
                    $responseContent['alertClass'] = 'warning';
                    $responseContent['alertMessage'] = 'Label ' . $supportLabel->getName() . ' already added to ticket';
                }
            }

            $responseContent['label'] = [
                'id' => $supportLabel->getId(),
                'name' => $supportLabel->getName(),
                'color' => $supportLabel->getColorCode(),
            ];

            return new Response(json_encode($responseContent), 200, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function getLabels($request = null)
    {
        static $labels;
        if (null !== $labels)
            return $labels;

        $qb = $this->em->createQueryBuilder();
        $qb->select('tl')->from('UVDeskCoreBundle:TicketLabel', 'tl')
                ->andwhere('tl.labelUser = :labelUserId')
                ->andwhere('tl.company = :companyId')
                ->setParameter('labelUserId', $this->getUser()->getId())
                ->setParameter('companyId', $this->getCompany()->getId());

        if($request) {
            $qb->andwhere("tl.name LIKE :labelName");
            $qb->setParameter('labelName', '%'.urldecode($request->query->get('query')).'%');
        }

        return $labels = $qb->getQuery()->getArrayResult();
    }

    public function loadTicketSearchFilterOptions(Request $request)
    {
        if (true === $request->isXmlHttpRequest()) {
            switch ($request->query->get('type')) {
                case 'agent':
                    $filtersResponse = $this->get('user.service')->getAgentPartialDataCollection($request);
                    break;
                case 'customer':
                    $filtersResponse = $this->get('user.service')->getCustomersPartial($request);
                    break;
                case 'group':
                    $filtersResponse = $this->get('user.service')->getGroups($request);
                    break;
                case 'team':
                    $filtersResponse = $this->get('user.service')->getSubGroups($request);
                    break;
                case 'tag':
                    $filtersResponse = $this->get('ticket.service')->getTicketTags($request);
                    break;
                case 'label':
                    $searchTerm = $request->query->get('query');
                    $entityManager = $this->getDoctrine()->getManager();

                    $supportLabelQuery = $entityManager->createQueryBuilder()->select('supportLabel')
                        ->from('UVDeskCoreBundle:SupportLabel', 'supportLabel')
                        ->where('supportLabel.user = :user')->setParameter('user', $this->get('user.service')->getSessionUser());
                    
                    if (!empty($searchTerm)) {
                        $supportLabelQuery->andWhere('supportLabel.name LIKE :labelName')->setParameter('labelName', '%' . urldecode($searchTerm) . '%');
                    }

                    $supportLabelCollection = $supportLabelQuery->getQuery()->getArrayResult();
                    return new Response(json_encode($supportLabelCollection), 200, ['Content-Type' => 'application/json']);
                    break;
                default:
                    break;
            }
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function loadTicketCollectionSearchFilterOptions(Request $request)
    {
        return new Response(json_encode([]), 404);
    }

    public function listTicketTypeCollectionXHR(Request $request)
    {
        if (true === $request->isXmlHttpRequest()) {
            $paginationResponse = $this->get('ticket.service')->paginateMembersTicketTypeCollection($request);

            return new Response(json_encode($paginationResponse), 200, ['Content-Type' => 'application/json']);
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function removeTicketTypeXHR($typeId, Request $request)
    {
        $json = [];
        if($request->getMethod() == "DELETE") {
            $em = $this->getDoctrine()->getManager();
            $id = $request->attributes->get('typeId');
            $type = $em->getRepository('UVDeskCoreBundle:TicketType')->find($id);

            // $this->get('event.manager')->trigger([
            //             'event' => 'type.deleted',
            //             'entity' => $type
            //         ]);

            $em->remove($type);
            $em->flush();

            $json['alertClass'] = 'success';
            $json['alertMessage'] = 'Success ! Type removed successfully.';
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function listTagCollectionXHR(Request $request)
    {
        if (true === $request->isXmlHttpRequest()) {
            $paginationResponse = $this->get('ticket.service')->paginateMembersTagCollection($request);

            return new Response(json_encode($paginationResponse), 200, ['Content-Type' => 'application/json']);
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function createTicketTagXHR(Request $request)
    {
        
    }

    public function updateTicketTagXHR($tagId)
    {
        
    }

    public function removeTicketTagXHR($tagId)
    {
        
    }
}
