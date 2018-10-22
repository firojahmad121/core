<?php

namespace Webkul\UVDesk\CoreBundle\Services;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Webkul\UVDesk\CoreBundle\Entity\Ticket;
use Webkul\UVDesk\CoreBundle\Entity\Thread;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\CoreBundle\Utils\TokenGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder;

class TicketService
{
    protected $container;
	protected $requestStack;
    protected $entityManager;
    protected $currentUser;
    public function __construct(ContainerInterface $container, RequestStack $requestStack, EntityManager $entityManager)
    {
        $this->container = $container;
		$this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    public function getUniqueReplyTo()
    {
        return sprintf("support.%s%s", TokenGenerator::generateToken(22, '0123456789abcdefghijklmnopqrstuvwxyz'), $this->container->getParameter('uvdesk.email_domain'));
    }

    public function getRandomRefrenceId()
    {
        return sprintf("<%s@mail.uvdesk.com>", TokenGenerator::generateToken(20, '0123456789abcdefghijklmnopqrstuvwxyz'));
    }
    public function getUser() {
        return $this->currentUser = ($this->currentUser ? $this->currentUser : $this->container->get('user.service')->getCurrentUser());
    }
    public function getDefaultType()
    {
        $typeCode = $this->container->getParameter('uvdesk.default.type');
        $ticketType = $this->entityManager->getRepository('UVDeskCoreBundle:TicketType')->findOneByCode($typeCode);

        return !empty($ticketType) ? $ticketType : null;
    }

    public function getDefaultStatus()
    {
        $statusCode = $this->container->getParameter('uvdesk.default.status');
        $ticketStatus = $this->entityManager->getRepository('UVDeskCoreBundle:TicketStatus')->findOneByCode($statusCode);

        return !empty($ticketStatus) ? $ticketStatus : null;
    }

    public function getDefaultPriority()
    {
        $priorityCode = $this->container->getParameter('uvdesk.default.priority');
        $ticketPriority = $this->entityManager->getRepository('UVDeskCoreBundle:TicketPriority')->findOneByCode($priorityCode);

        return !empty($ticketPriority) ? $ticketPriority : null;
    }

    public function appendTwigSnippet($snippet = '')
    {
        switch ($snippet) {
            case 'createMemberTicket':
                return $this->getMemberCreateTicketSnippet();
                break;
            default:
                break;
        }

        return '';
    }

    public function getMemberCreateTicketSnippet()
    {
        $twigTemplatingEngine = $this->container->get('twig');
        $ticketTypeCollection = $this->entityManager->getRepository('UVDeskCoreBundle:TicketType')->findAll();
        
        return $twigTemplatingEngine->render('@UVDeskCore/Snippets/createMemberTicket.html.twig', [
            'ticketTypeCollection' => $ticketTypeCollection
        ]);
    }

    public function createTicket(array $params = [])
    {
        $thread = $this->entityManager->getRepository('UVDeskCoreBundle:Thread')->findOneByMessageId($params['messageId']);

        if (empty($thread)) {
            $user = $this->entityManager->getRepository('UVDeskCoreBundle:User')->findOneByEmail($params['from']);

            if (empty($user) || null == $user->getCustomerInstance()) {
                $role = $this->entityManager->getRepository('UVDeskCoreBundle:SupportRole')->findOneByCode($params['role']);
                if (empty($role)) {
                    throw new \Exception("The requested role '" . $params['role'] . "' does not exist.");
                }
                
                // Create User Instance
                $user = $this->container->get('user.service')->createUserInstance($params['from'], $params['name'], $role, [
                    'source' => strtolower($params['source']),
                ]);
            }

            $params['role'] = 4;
            $params['mailbox'] = $this->container->get('mailbox.service')->getMailbox(current($params['replyTo'])); 
            $params['customer'] = $params['user'] = $user;

            return $this->createTicketBase($params);
        }

        return;
    }

    public function createTicketBase(array $ticketData = [])
    {
        if ('website' == $ticketData['source']) {
            $ticketData['messageId'] = $this->getRandomRefrenceId();
        }

        // Set Defaults
        $ticketType = !empty($ticketData['type']) ? $ticketData['type'] : $this->getDefaultType();
        $ticketStatus = !empty($ticketData['status']) ? $ticketData['status'] : $this->getDefaultStatus();
        $ticketPriority = !empty($ticketData['priority']) ? $ticketData['priority'] : $this->getDefaultPriority();
        $ticketMailbox = !empty($ticketData['mailbox']) ? $ticketData['mailbox'] : $this->container->get('mailbox.service')->getDefaultMailbox();

        $ticketData['type'] = $ticketType;
        $ticketData['status'] = $ticketStatus;
        $ticketData['mailbox'] = $ticketMailbox;
        $ticketData['priority'] = $ticketPriority;
        $ticketData['uniqueReplyTo'] = $this->getUniqueReplyTo();
        $ticketData['messageId'] = 'website' == $ticketData['source'] ? $this->getRandomRefrenceId() : (!empty($ticketData['messageId']) ? $ticketData['messageId'] : null);
        $ticketData['isTrashed'] = false;

        $ticket = new Ticket();
        foreach ($ticketData as $property => $value) {
            $callable = 'set' . ucwords($property);

            if (method_exists($ticket, $callable)) {
                $ticket->$callable($value);
            }
        }

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $this->createThread($ticket, $ticketData);
    }

    public function createThread(Ticket $ticket, array $threadData)
    {
        $threadData['isLocked'] = 0;
        // $this->ticketLastReply = $this->getLastReply($ticket->getId(), false);
        
        if ('forward' === $threadData['threadType']) {
            $threadData['replyTo'] = $threadData['to'];
        }
        
        $collaboratorEmails = array_merge(!empty($threadData['cccol']) ? $threadData['cccol'] : [], !empty($threadData['cc']) ? $threadData['cc'] : []);
        if (!empty($collaboratorEmails)) {
            $threadData['cc'] = $collaboratorEmails;
        }
                
        $thread = new Thread();
        $thread->setTicket($ticket);
        $thread->setCreatedAt(new \DateTime());
        $thread->setUpdatedAt(new \DateTime());

        foreach ($threadData as $property => $value) {
            if (!empty($value)) {
                $callable = 'set' . ucwords($property);
    
                if (method_exists($thread, $callable)) {
                    $thread->$callable($value);
                }
            }
        }

        if ('reply' === $threadData['threadType']) {
            if ('agent' === $threadData['createdBy']) {
                // Ticket has been updated by support agents, mark as agent replied | customer view pending
                $ticket->setIsCustomerViewed(false);
                $ticket->setIsReplied(true);
            } else {
                // Ticket has been updated by customer, mark as agent view | reply pending
                $ticket->setIsAgentViewed(false);
                $ticket->setIsReplied(false);
            }
            // dump($ticket);die;
            $this->entityManager->persist($ticket);
        } else if ('create' === $threadData['threadType']) {
            $ticket->setIsReplied(false);
            $this->entityManager->persist($ticket);
        }
        // dump($thread);die;
        $this->entityManager->persist($thread);
        $this->entityManager->flush();

        return $thread;
    }

    public function getTypes()
    {
        static $types;
        if (null !== $types)
            return $types;

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('tp.id','tp.code As name')->from('UVDeskCoreBundle:TicketType', 'tp')
                ->andwhere('tp.isActive = 1');

        return $types = $qb->getQuery()->getArrayResult();
    }

    public function getStatus()
    {
        static $statuses;
        if (null !== $statuses)
            return $statuses;

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('ts')->from('UVDeskCoreBundle:TicketStatus', 'ts');
        // $qb->orderBy('ts.sortOrder', Criteria::ASC);

        return $statuses = $qb->getQuery()->getArrayResult();
    }

    public function getTicketTotalThreads($ticketId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(th.id) as threadCount')->from('UVDeskCoreBundle:Ticket', 't')
            ->leftJoin('t.threads', 'th')
            ->andWhere('t.id = :ticketId')
            ->andWhere('th.threadType = :threadType')
            ->setParameter('threadType','reply')
            ->setParameter('ticketId', $ticketId);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id) as threadCount')->from('UVDeskCoreBundle:Thread', 't')
            ->andWhere('t.ticket = :ticketId')
            ->andWhere('t.threadType = :threadType')
            ->setParameter('threadType','reply')
            ->setParameter('ticketId', $ticketId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function paginateMembersTicketCollection(Request $request)
    {
        $params = $request->query->all();
        $activeUser = $this->container->get('user.service')->getSessionUser();
        $ticketRepository = $this->entityManager->getRepository('UVDeskCoreBundle:Ticket');

        // Get base query
        // dump($params);die;
        $baseQuery = $ticketRepository->prepareBaseTicketQuery($activeUser, $params);
        $ticketTabs = $ticketRepository->getTicketTabDetails($params);

        // Add reply count filter to base query
        if (array_key_exists('repliesLess', $params) || array_key_exists('repliesMore', $params)) {
            $baseQuery->leftJoin('t.threads', 'th')
                ->andWhere('th.threadType = :threadType')->setParameter('threadType', 'reply')
                ->groupBy('t.id');

            if (array_key_exists('repliesLess', $params)) {
                $baseQuery->andHaving('count(th.id) < :threadValueLesser')->setParameter('threadValueLesser', intval($params['repliesLess']));
            }

            if (array_key_exists('repliesMore', $params)) {
                $baseQuery->andHaving('count(th.id) > :threadValueGreater')->setParameter('threadValueGreater', intval($params['repliesMore']));
            }
        }

        // Apply Pagination
        $pageNumber = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsLimit = !empty($params['limit']) ? (int) $params['limit'] : $ticketRepository::DEFAULT_PAGINATION_LIMIT;

        if (isset($params['repliesLess']) || isset($params['repliesMore'])) {
            $paginationOptions = ['wrap-queries' => true];
            $paginationQuery = $baseQuery->getQuery()
                ->setHydrationMode(Query::HYDRATE_ARRAY);
        } else {
            $paginationOptions = ['distinct' => true];
            $paginationQuery = $baseQuery->getQuery()
                ->setHydrationMode(Query::HYDRATE_ARRAY)
                ->setHint('knp_paginator.count', isset($params['status']) ? $ticketTabs[$params['status']] : $ticketTabs[1]);
        }

        $pagination = $this->container->get('knp_paginator')->paginate($paginationQuery, $pageNumber, $itemsLimit, $paginationOptions);
        // Process Pagination Response
        $ticketCollection = [];
        $paginationParams = $pagination->getParams();
        $paginationData = $pagination->getPaginationData();

        $paginationParams['page'] = 'replacePage';
        $paginationData['url'] = '#' . $this->container->get('uvdesk.service')->buildPaginationQuery($paginationParams);
        // $container->get('default.service')->buildSessionUrl('ticket',$queryParameters);


        $ticketThreadCountQueryTemplate = $this->entityManager->createQueryBuilder()
            ->select('COUNT(thread.id) as threadCount')
            ->from('UVDeskCoreBundle:Ticket', 'ticket')
            ->leftJoin('ticket.threads', 'thread')
            ->where('ticket.id = :ticketId')
            ->andWhere('thread.threadType = :threadType')->setParameter('threadType', 'reply');
        
        // $ticketAttachmentCountQueryTemplate = $this->entityManager->createQueryBuilder()
        //     ->select('DISTINCT COUNT(attachment.id) as attachmentCount')
        //     ->from('UVDeskCoreBundle:Thread', 'thread')
        //     ->leftJoin('thread.ticket', 'ticket')
        //     ->leftJoin('thread.attachments', 'attachment')
        //     ->andWhere('ticket.id = :ticketId');
        
        foreach ($pagination->getItems() as $ticketDetails) {
            $ticket = array_shift($ticketDetails);

            $ticketThreadCountQuery = clone $ticketThreadCountQueryTemplate;
            $ticketThreadCountQuery->setParameter('ticketId', $ticket['id']);

            // $ticketAttachmentCountQuery = clone $ticketAttachmentCountQueryTemplate;
            // $ticketAttachmentCountQuery->setParameter('ticketId', $ticket['id']);

            $totalTicketReplies = (int) $ticketThreadCountQuery->getQuery()->getSingleScalarResult();
            // $ticketHasAttachments = (bool) (int) $ticketAttachmentCountQuery->getQuery()->getSingleScalarResult();
            $ticketHasAttachments = false;

            $ticketResponse = [
                'id' => $ticket['id'],
                'subject' => $ticket['subject'],
                'isStarred' => $ticket['isStarred'],
                'isAgentView' => $ticket['isAgentViewed'],
                'isTrashed' => $ticket['isTrashed'],
                'source' => $ticket['source'],
                'group' => $ticketDetails['groupName'],
                'team' => $ticketDetails['teamName'],
                'priority' => $ticket['priority']['description'],
                'type' => $ticketDetails['typeName'],
                'timestamp' => $ticket['createdAt']->getTimestamp(),
                'formatedCreatedAt' => $ticket['createdAt']->format('d-m-Y h:ia'),
                'totalThreads' => $totalTicketReplies,
                'agent' => null,
                'customer' => null,
                'hasAttachments' => $ticketHasAttachments
            ];

            if (!empty($ticketDetails['agentId'])) {
                $ticketResponse['agent'] = [
                    'id' => $ticketDetails['agentId'],
                    'name' => $ticketDetails['agentName'],
                    'smallThumbnail' => $ticketDetails['smallThumbnail'],
                ];
            }

            if (!empty($ticketDetails['customerId'])) {
                $ticketResponse['customer'] = [
                    'id' => $ticketDetails['customerId'],
                    'name' => $ticketDetails['customerName'],
                    'email' => $ticketDetails['customerEmail'],
                    'smallThumbnail' => $ticketDetails['customersmallThumbnail'],
                ];
            }

            array_push($ticketCollection, $ticketResponse);
        }
         
        return [
            'tickets' => $ticketCollection,
            'pagination' => $paginationData,
            'tabs'=>$ticketTabs,
            'labels' => [
                'predefind' => $this->getPredefindLabelDetails($this->container),
                'custom' => $this->getCustomLabelDetails($this->container),
            ],
          
        ];
    }
    

    public function getPredefindLabelDetails($container) {
        $currentUser = $container->get('user.service')->getCurrentUser();
        $data = array();
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(DISTINCT t.id) as ticketCount')->from('UVDeskCoreBundle:Ticket', 't');
        $qb->andwhere('t.isTrashed != 1');

        //Can be reomved
        // $qb->andwhere('t.status != 3 AND t.status != 4  AND t.status != 5 ');
        $data['all'] = $qb->getQuery()->getSingleScalarResult();
        $newCount = 0;
        $newQb = clone $qb;
        $newQb->andwhere('t.isNew = 1');
        $data['new'] = $newQb->getQuery()->getSingleScalarResult();

        $qb->andwhere("t.agent is NULL");
        $data['unassigned'] = $qb->getQuery()->getSingleScalarResult();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(DISTINCT t.id) as ticketCount')->from('UVDeskCoreBundle:Ticket', 't');
        $qb->andwhere('t.isTrashed != 1');
        $qb->andwhere('t.isReplied = 0');
        $qb->andwhere('t.status != 5');
        $data['notreplied'] = $qb->getQuery()->getSingleScalarResult();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id) as ticketCount')->from('UVDeskCoreBundle:Ticket', 't')
                ->andwhere('t.status != 3 AND t.status != 4  AND t.status != 5 ')
                ->andWhere("t.agent = :agentId")
                ->andwhere('t.isTrashed != 1')
                ->setParameter('agentId', $currentUser->getId());

        $data['mine'] = $qb->getQuery()->getSingleScalarResult();


        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id) as ticketCount')->from('UVDeskCoreBundle:Ticket', 't');
                $qb->andwhere('t.isStarred = 1')
                ->andwhere('t.isTrashed != 1');

        //Can be reomved
        $qb->andwhere('t.status != 3 AND t.status != 4  AND t.status != 5 ');
        $data['starred'] = $qb->getQuery()->getSingleScalarResult();


        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id) as ticketCount')->from('UVDeskCoreBundle:Ticket', 't');
        $qb->andwhere('t.isTrashed = 1');

        $result = $qb->getQuery()->getResult();
        $data['trashed'] = $qb->getQuery()->getSingleScalarResult();

        return $data;
    }

    public function paginateMembersTicketThreadCollection(Ticket $ticket, Request $request)
    {
        $params = $request->query->all();
        $activeUser = $this->container->get('user.service')->getSessionUser();
        $threadRepository = $this->entityManager->getRepository('UVDeskCoreBundle:Thread');

        // Get base query
        $enableLockedThreads = $this->container->get('user.service')->isAccessAuthorized('ROLE_AGENT_MANAGE_LOCK_AND_UNLOCK_THREAD');
        $baseQuery = $threadRepository->prepareBasePaginationRecentThreadsQuery($ticket, $params, $enableLockedThreads);

        // Apply Pagination
        $paginationItemsQuery = clone $baseQuery;
        $totalPaginationItems = $paginationItemsQuery->select('COUNT(DISTINCT thread.id)')->getQuery()->getSingleScalarResult();
        
        $pageNumber = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsLimit = !empty($params['limit']) ? (int) $params['limit'] : $threadRepository::DEFAULT_PAGINATION_LIMIT;
        
        $paginationOptions = ['distinct' => true];
        $paginationQuery = $baseQuery->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->setHint('knp_paginator.count', (int) $totalPaginationItems);

        $pagination = $this->container->get('knp_paginator')->paginate($paginationQuery, $pageNumber, $itemsLimit, $paginationOptions);

        // Process Pagination Response
        $threadCollection = [];
        $paginationParams = $pagination->getParams();
        $paginationData = $pagination->getPaginationData();

        if (!empty($params['threadRequestedId'])) {
            $requestedThreadCollection = $baseQuery
                ->andWhere('thread.id >= :threadRequestedId')->setParameter('threadRequestedId', (int) $params['threadRequestedId'])
                ->getQuery()->getArrayResult();
            
            $totalRequestedThreads = count($requestedThreadCollection);
            $paginationData['current'] = ceil($totalRequestedThreads / $threadRepository::DEFAULT_PAGINATION_LIMIT);

            if ($paginationData['current'] > 1) {
                $paginationData['firstItemNumber'] = 1;
                $paginationData['lastItemNumber'] = $totalRequestedThreads;
                $paginationData['next'] = ceil(($totalRequestedThreads + 1) / $threadRepository::DEFAULT_PAGINATION_LIMIT);
            }
        }

        $paginationParams['page'] = 'replacePage';
        $paginationData['url'] = '#' . $this->container->get('uvdesk.service')->buildPaginationQuery($paginationParams);

        foreach ($pagination->getItems() as $threadDetails) {
            $threadResponse = [
                'id' => $threadDetails['id'],
                'user' => null,
                'fullname' => null,
				'reply' => utf8_decode($threadDetails['message']),
				'source' => $threadDetails['source'],
                'threadType' => $threadDetails['threadType'],
                'userType' => $threadDetails['createdBy'],
                'timestamp' => $threadDetails['createdAt']->getTimestamp(),
                'formatedCreatedAt' => $threadDetails['createdAt']->format('d-m-Y h:ia'),
                'bookmark' => $threadDetails['isBookmarked'],
                'isLocked' => $threadDetails['isLocked'],
                'replyTo' => $threadDetails['replyTo'],
                'cc' => $threadDetails['cc'],
                'bcc' => $threadDetails['bcc'],
                'attachments' => [],
            ];

            if (!empty($threadDetails['user'])) {
                $threadResponse['fullname'] = trim($threadDetails['user']['firstName'] . ' ' . $threadDetails['user']['lastName']);
                $threadResponse['user'] = [
                    'id' => $threadDetails['user']['id'],
                    'name' => $threadResponse['fullname'],
                    // 'smallThumbnail' => $threadDetails['smallThumbnail'],
                ];
            }

            array_push($threadCollection, $threadResponse);
        }

        return [
            'threads' => $threadCollection,
            'pagination' => $paginationData,
        ];
    }

    public function massXhrUpdate(Request $request)
    {
        $permissionMessages = [
            'trashed' => ['permission' => 'ROLE_AGENT_DELETE_TICKET', 'message' => 'Success ! Tickets moved to trashed successfully.'],
            'delete' => ['permission' =>  'ROLE_AGENT_DELETE_TICKET', 'message' => 'Success ! Tickets removed successfully.'],
            'restored' => ['permission' =>  'ROLE_AGENT_RESTORE_TICKET', 'message' => 'Success ! Tickets restored successfully.'],
            'agent' => ['permission' =>  'ROLE_AGENT_ASSIGN_TICKET', 'message' => 'Success ! Agent assigned successfully.'],
            'status' => ['permission' =>  'ROLE_AGENT_UPDATE_TICKET_STATUS', 'message' => 'Success ! Tickets status updated successfully.'],
            'type' => ['permission' =>  'ROLE_AGENT_ASSIGN_TICKET_TYPE', 'message' => 'Success ! Tickets type updated successfully.'],
            'group' => ['permission' =>  'ROLE_AGENT_ASSIGN_TICKET_GROUP', 'message' => 'Success ! Tickets group updated successfully.'],
            'team' => ['permission' =>  'ROLE_AGENT_ASSIGN_TICKET_GROUP', 'message' => 'Success ! Tickets team updated successfully.'],
            'priority' => ['permission' =>  'ROLE_AGENT_UPDATE_TICKET_PRIORITY', 'message' => 'Success ! Tickets priority updated successfully.'],
            'label' => ['permission' =>  '', 'message' => 'Success ! Tickets added to label successfully.']
        ];
        $json = array();
        $data = $request->request->get('data');
        
        $ids = $data['ids'];        
        foreach ($ids as $id) {
            $ticket = $this->entityManager->getRepository('UVDeskCoreBundle:Ticket')->find($id);
            if(!$ticket)
                continue;

            switch($data['actionType']) {
                case 'trashed':
                    $ticket->setIsTrashed(1);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();                  
                    break;
                case 'delete':

                    $this->entityManager->remove($ticket);
                    $this->entityManager->flush();
                    break;
                case 'restored':
                    $ticket->setIsTrashed(0);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();

                   
                    break;
                case 'agent':
                    $flag = 0;
                    $agent = $this->entityManager->getRepository('UVDeskCoreBundle:User')->find($data['targetId']);
                    $targetAgent = $agent->getUserInstance()['agent'] ? $agent->getUserInstance()['agent']->getName() : 'UnAssigned';
                    if($ticket->getAgent() != $agent) {
                        $ticketAgent = $ticket->getAgent();
                        $currentAgent = $ticketAgent ? ($ticketAgent->getUserInstance()['agent'] ? $ticketAgent->getUserInstance()['agent']->getName() : 'UnAssigned') : 'UnAssigned';

                        $notePlaceholders = $this->getNotePlaceholderValues(
                                $currentAgent,
                                $targetAgent,
                                'agent'
                            );
                        $flag = 1;
                    }

                    $ticket->setAgent($agent);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();

                    break;
                case 'status':
                    $status = $this->entityManager->getRepository('UVDeskCoreBundle:TicketStatus')->find($data['targetId']);
                    $flag = 0;
                    // dump($ticket->getStatus());die;
                    if($ticket->getStatus() != $status) {
                        $notePlaceholders = $this->getNotePlaceholderValues(
                                $ticket->getStatus()->getCode(),
                                $status->getCode(),
                                'status'
                            );
                        $flag = 1;
                    }
                    $ticket->setStatus($status);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();

                    //Event Triggered
                    // if($flag) {
                    //     $this->container->get('event.manager')->trigger([
                    //             'event' => 'ticket.status.updated',
                    //             'entity' => $ticket,
                    //             'targetEntity' => $status,
                    //             'notePlaceholders' => $notePlaceholders
                    //         ]);
                    // }
                    break;
                case 'type':
                    $type = $this->entityManager->getRepository('UVDeskCoreBundle:TicketType')->find($data['targetId']);
                    $flag = 0;
                    if($ticket->getType() != $type) {
                        $notePlaceholders = $this->getNotePlaceholderValues(
                                $ticket->getType() ? $ticket->getType()->getCode() :'UnAssigned',
                                $type->getCode(),
                                'status'
                            );
                        $flag = 1;
                    }
                    $ticket->setType($type);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();

                    break;
                case 'group':
                    $group = $this->entityManager->getRepository('UVDeskCoreBundle:SupportGroup')->find($data['targetId']);
                    $flag = 0;
                    if($ticket->getSupportGroup() != $group) {
                        $notePlaceholders = $this->getNotePlaceholderValues(
                                    $ticket->getSupportGroup() ? $ticket->getSupportGroup()->getName() : 'UnAssigned',
                                    $group ? $group->getName() :'UnAssigned',
                                    'group'
                                );
                        $flag = 1;
                    }
                    $ticket->setSupportGroup($group);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();

                    break;
                case 'team':
                    $team = $this->entityManager->getRepository('UVDeskCoreBundle:SupportTeam')->find($data['targetId']);
                    $flag = 0;
                    if($ticket->getSupportTeam() != $team){
                        $notePlaceholders = $this->getNotePlaceholderValues(
                                $ticket->getSupportTeam() ? $ticket->getSupportTeam()->getName() :'UnAssigned',
                                $team ? $team->getName() :'UnAssigned',
                                'team'
                            );
                        $flag = 1;
                    }
                    $ticket->setSupportTeam($team);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();
                    break;
                case 'priority':
                    $flag = 0;
                    $priority = $this->entityManager->getRepository('UVDeskCoreBundle:TicketPriority')->find($data['targetId']);
                   
                    if($ticket->getPriority() != $priority) {
                        $notePlaceholders = $this->getNotePlaceholderValues(
                                    $ticket->getPriority()->getCode(),
                                    $priority->getCode(),
                                    'priority'
                                );
                        $flag = 1;
                    }
                    $ticket->setPriority($priority);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();

                    
                    break;
                case 'label':
                    $label = $this->entityManager->getRepository('UVDeskCoreBundle:SupportLabel')->find($data['targetId']);
                    if($label && !$this->entityManager->getRepository('UVDeskCoreBundle:Ticket')->isLabelAlreadyAdded($ticket, $label))
                        $ticket->addSupportLabel($label);
                    $this->entityManager->persist($ticket);
                    $this->entityManager->flush();
                    break;
            }
        }
        return [
            'alertClass' => 'success',
            'alertMessage' => $permissionMessages[$data['actionType']]['message'],
        ];
    }

    public function getNotePlaceholderValues($currentProperty,$targetProperty,$type = "", $details = null) {
        $variables = array();

        $variables['type.previousType'] = ($type == 'type') ? $currentProperty : '';
        $variables['type.updatedType'] = ($type == 'type') ? $targetProperty : '';

        $variables['status.previousStatus'] = ($type == 'status') ? $currentProperty : '';
        $variables['status.updatedStatus'] = ($type == 'status') ? $targetProperty : '';

        $variables['group.previousGroup'] = ($type == 'group') ? $currentProperty : '';
        $variables['group.updatedGroup'] = ($type == 'group') ? $targetProperty : '';

        $variables['team.previousTeam'] = ($type == 'team') ? $currentProperty : '';
        $variables['team.updatedTeam'] = ($type == 'team') ? $targetProperty : '';

        $variables['priority.previousPriority'] = ($type == 'priority') ? $currentProperty : '';
        $variables['priority.updatedPriority'] = ($type == 'priority') ? $targetProperty : '';

        $variables['agent.previousAgent'] = ($type == 'agent') ? $currentProperty : '';
        $variables['agent.updatedAgent'] = ($type == 'agent') ? $targetProperty : '';

        if($details) {
            $variables['agent.responsePerformingAgent'] = $details;
        } else {
            $detail = $this->getUser()->getUserInstance();
            $variables['agent.responsePerformingAgent'] = !empty($detail['agent']) ? $detail['agent']->getName() : '';
        }
        return $variables;
    }
    public function paginateMembersTicketTypeCollection(Request $request)
    {
        // Get base query
        $params = $request->query->all();
        $ticketRepository = $this->entityManager->getRepository('UVDeskCoreBundle:Ticket');
        $paginationQuery = $ticketRepository->prepareBasePaginationTicketTypesQuery($params);

        // Apply Pagination
        $paginationOptions = ['distinct' => true];
        $pageNumber = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsLimit = !empty($params['limit']) ? (int) $params['limit'] : $ticketRepository::DEFAULT_PAGINATION_LIMIT;

        $pagination = $this->container->get('knp_paginator')->paginate($paginationQuery, $pageNumber, $itemsLimit, $paginationOptions);

        // Process Pagination Response
        $paginationParams = $pagination->getParams();
        $paginationData = $pagination->getPaginationData();

        $paginationParams['page'] = 'replacePage';
        $paginationData['url'] = '#' . $this->container->get('uvdesk.service')->buildPaginationQuery($paginationParams);

        return [
            'types' => array_map(function ($ticketType) {
                return [
                    'id' => $ticketType->getId(),
                    'code' => strtoupper($ticketType->getCode()),
                    'description' => $ticketType->getDescription(),
                    'isActive' => $ticketType->getIsActive(),
                ];
            }, $pagination->getItems()),
            'pagination_data' => $paginationData,
        ];
    }

    public function paginateMembersTagCollection(Request $request)
    {
        // Get base query
        $params = $request->query->all();
        $ticketRepository = $this->entityManager->getRepository('UVDeskCoreBundle:Ticket');
        $baseQuery = $ticketRepository->prepareBasePaginationTagsQuery($params);
        
        // Apply Pagination
        $paginationResultsQuery = clone $baseQuery;
        $paginationResultsQuery->select('COUNT(supportTag.id)');
        $paginationQuery = $baseQuery->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->setHint('knp_paginator.count', $paginationResultsQuery->getQuery()->getResult());

        $paginationOptions = ['distinct' => true];
        $pageNumber = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsLimit = !empty($params['limit']) ? (int) $params['limit'] : $ticketRepository::DEFAULT_PAGINATION_LIMIT;

        $pagination = $this->container->get('knp_paginator')->paginate($paginationQuery, $pageNumber, $itemsLimit, $paginationOptions);

        // Process Pagination Response
        $paginationParams = $pagination->getParams();
        $paginationData = $pagination->getPaginationData();

        $paginationParams['page'] = 'replacePage';
        $paginationData['url'] = '#' . $this->container->get('uvdesk.service')->buildPaginationQuery($paginationParams);

        return [
            'tags' => array_map(function ($supportTag) {
                return [
                    'id' => $supportTag['id'],
                    'name' => $supportTag['name'],
                    'ticketCount' => $supportTag['totalTickets'],
                    'articleCount' => 0,
                ];
            }, $pagination->getItems()),
            'pagination_data' => $paginationData,
        ];
    }

    public function getTicketInitialThreadDetails(Ticket $ticket)
    {
        $initialThread = $this->entityManager->getRepository('UVDeskCoreBundle:Thread')->findOneBy([
            'ticket' => $ticket,
            'threadType' => 'create',
        ]);

        if (!empty($initialThread)) {
            $author = $initialThread->getUser();
            $authorInstance = 'agent' == $initialThread->getCreatedBy() ? $author->getAgentInstance() : $author->getCustomerInstance();

            return [
                'id' => $initialThread->getId(),
                'source' => $initialThread->getSource(),
                'messageId' => $initialThread->getMessageId(),
                'threadType' => $initialThread->getThreadType(),
                'createdBy' => $initialThread->getCreatedBy(),
                'message' => $initialThread->getMessage(),
                'attachments' => [],
                'timestamp' => $initialThread->getCreatedAt()->getTimestamp(),
                'createdAt' => $initialThread->getCreatedAt()->format('d-m-Y h:ia'),
                'user' => $authorInstance->getPartialDetails(),
            ];
        }

        return null;
    }

    public function getCreateReply($ticketId,$cacheRequired = true)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("DISTINCT th,u.id as userId")->from('UVDeskCoreBundle:Thread', 'th')
                ->leftJoin('th.ticket','t')
                ->leftJoin('th.user','u')
                ->andWhere('t.id = :ticketId')
                ->andWhere('th.threadType = :threadType')
                ->setParameter('threadType','create')
                ->setParameter('ticketId',$ticketId)
                ->orderBy('th.id', 'ASC');

        $result = $qb->getQuery()->getArrayResult();
        if($result) {
            $userService = $this->container->get('user.service');
            $data = $result[0][0];
            if(isset($data['userType']) && $data['userType'] == 'agent')
                $data['user'] = $userService->getAgentPartialDetailById($result[0]['userId']);
            else
                $data['user'] = $userService->getCustomerPartialDetailById($result[0]['userId']);

            // $data['attachments'] = $cacheRequired ? $this->container->get('file.service')->getCachedAttachments($data['attachments']) : $data['attachments'];
            // $data['formatedCreatedAt'] = $userService->convertToTimezone($data['createdAt']);
            // $data['timestamp'] = $userService->convertToDatetimeTimezoneTimestamp($data['createdAt']);
            $data['reply'] = utf8_decode($data['message']);
            return $data;
        } else
            return null;
    }

    public function hasAttachments($ticketId) {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("DISTINCT COUNT(a.id) as attachmentCount")->from('UVDeskCoreBundle:Thread', 'th')
                ->leftJoin('th.ticket','t')
                ->leftJoin('th.attachments','a')
                ->andWhere('t.id = :ticketId')
                ->setParameter('ticketId',$ticketId);

        return intval($qb->getQuery()->getSingleScalarResult());
    }
    public function getAgentDraftReply($ticketId, $draftType)
    {
        return '';
        // $userId = $this->getUser()->getId();
        // $companyId = $this->getCompany()->getId();
        // $qb = $this->em->createQueryBuilder();
        // $qb->select('d')->from("UVDeskCoreBundle:Draft", 'd')
        //         ->andwhere('d.ticket = :ticketId')
        //         ->andwhere("d.field = '".$draftType."'")
        //         ->andwhere('d.user = :userId')
        //         ->andwhere("d.userType = 'agent'")
        //         ->setParameter('ticketId',$ticketId)
        //         ->setParameter('userId', $this->getUser()->getId());

        // $result = $qb->getQuery()->getOneOrNullResult();

        // if($result && trim(strip_tags($result->getContent())) ) {
        //     return $result->getContent();
        // }

        // $data = $this->container->get('user.service')->getUserDetailById($userId,$companyId);

        // return str_replace( "\n", '<br/>',$data->getSignature());
    }

    // public function getTicketTasks($ticketId) {
    //     $qb = $this->em->createQueryBuilder();
    //     $qb->select('DISTINCT tsk')->from('UVDeskCoreBundle:Task', 'tsk')
    //             ->leftJoin('tsk.followers', 'fl')
    //             ->andwhere('tsk.ticket = :ticketId')
    //             ->andwhere('tsk.company = :companyId')
    //             ->setParameter('ticketId', $ticketId)
    //             ->setParameter('companyId', $this->getCompany()->getId());

    //     $user = $this->getUser();
    //     if($user->getRole() == "ROLE_AGENT") {
    //         $qb->andWhere("tsk.assignedAgent = :agentId OR tsk.user = :userId OR fl.id =:followerId")
    //                 ->setParameter('agentId', $user->getId())
    //                 ->setParameter('userId', $user->getId())
    //                 ->setParameter('followerId', $user->getId());
    //     }

    //     $results = $qb->getQuery()->getResult();
    //     return $results;
    // }

    public function getTicketConditions()
    {
        $conditions = array(
                        'ticket' => [
                            $this->trans('mail') => array(
                                        [
                                            'lable' => $this->trans('from_mail'),
                                            'value' => 'from_mail',
                                            'match' => 'email'
                                        ],
                                        [
                                            'lable' => $this->trans('to_mail'),
                                            'value' => 'to_mail',
                                            'match' => 'email'
                                        ],
                                    ),
                            $this->trans('ticket') => array(
                                        [
                                            'lable' => $this->trans('subject'),
                                            'value' => 'subject',
                                            'match' => 'string'
                                        ],
                                        [
                                            'lable' => $this->trans('description'),
                                            'value' => 'description',
                                            'match' => 'string'
                                        ],
                                        [
                                            'lable' => $this->trans('subject_or_description'),
                                            'value' => 'subject_or_description',
                                            'match' => 'string'
                                        ],
                                        [
                                            'lable' => $this->trans('priority'),
                                            'value' => 'TicketPriority',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('type'),
                                            'value' => 'TicketType',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('status'),
                                            'value' => 'TicketStatus',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('source'),
                                            'value' => 'source',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('created'),
                                            'value' => 'created',
                                            'match' => 'date'
                                        ],
                                        [
                                            'lable' => $this->trans('agent'),
                                            'value' => 'agent',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('group'),
                                            'value' => 'group',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('team'),
                                            'value' => 'team',
                                            'match' => 'select'
                                        ],
                                    ),
                            $this->trans('customer') => array(
                                        [
                                            'lable' => $this->trans('customer_name'),
                                            'value' => 'customer_name',
                                            'match' => 'string'
                                        ],
                                        [
                                            'lable' => $this->trans('customer_email'),
                                            'value' => 'customer_email',
                                            'match' => 'email'
                                        ],
                                    ),
                        ],
                        'task' => [
                            $this->trans('task') => array(
                                        [
                                            'lable' => $this->trans('subject'),
                                            'value' => 'subject',
                                            'match' => 'string'
                                        ],
                                        [
                                            'lable' => $this->trans('description'),
                                            'value' => 'description',
                                            'match' => 'string'
                                        ],
                                        [
                                            'lable' => $this->trans('subject_or_description'),
                                            'value' => 'subject_or_description',
                                            'match' => 'string'
                                        ],
                                        [
                                            'lable' => $this->trans('priority'),
                                            'value' => 'TicketPriority',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('stage'),
                                            'value' => 'stage',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('created'),
                                            'value' => 'created',
                                            'match' => 'date'
                                        ],
                                        [
                                            'lable' => $this->trans('agent_name'),
                                            'value' => 'agent_name',
                                            'match' => 'select'
                                        ],
                                        [
                                            'lable' => $this->trans('agent_email'),
                                            'value' => 'agent_email',
                                            'match' => 'select'
                                        ],
                                    ),
                        ]
        );

    //     $cfConditions = [];
    //     //if($this->container->get('user.service')->checkCompanyPermission('custom_fields') ) {
    //         $customFields = $this->container->get('customfield.service')->getCustomFieldsArray('both');

    //         foreach($customFields as $customField) {
    //             $cfConditions[] = [
    //                         'lable' => $customField['name'],
    //                         'value' => 'customFields[' . $customField['id'] . ']',
    //                         'match' => $this->getMatchTypeByFieldType($customField),
    //                     ];
    //         }
    //    //}

    //     if(count($cfConditions)) {
    //         $conditions['ticket'][$this->trans('Custom Fields')] = $cfConditions;
    //     }
        return $conditions;
    }


    public function getTicketMatchConditions()
    {
        return [
                'email' => array(
                            [
                                'lable' => $this->trans('is'),
                                'value' => 'is'
                            ],
                            [
                                'lable' => $this->trans('isNot'),
                                'value' => 'isNot'
                            ],
                            [
                                'lable' => $this->trans('contains'),
                                'value' => 'contains'
                            ],
                            [
                                'lable' => $this->trans('notContains'),
                                'value' => 'notContains'
                            ],
                        ),
                'string' => array(
                            [
                                'lable' => $this->trans('is'),
                                'value' => 'is'
                            ],
                            [
                                'lable' => $this->trans('isNot'),
                                'value' => 'isNot'
                            ],
                            [
                                'lable' => $this->trans('contains'),
                                'value' => 'contains'
                            ],
                            [
                                'lable' => $this->trans('notContains'),
                                'value' => 'notContains'
                            ],
                            [
                                'lable' => $this->trans('startWith'),
                                'value' => 'startWith'
                            ],
                            [
                                'lable' => $this->trans('endWith'),
                                'value' => 'endWith'
                            ],
                        ),
                'select' => array(
                            [
                                'lable' => $this->trans('is'),
                                'value' => 'is'
                            ],
                            [
                                'lable' => $this->trans('isNot'),
                                'value' => 'isNot'
                            ],
                        ),
                'date' => array(
                            [
                                'lable' => $this->trans('before'),
                                'value' => 'before'
                            ],
                            [
                                'lable' => $this->trans('beforeOn'),
                                'value' => 'beforeOn'
                            ],
                            [
                                'lable' => $this->trans('after'),
                                'value' => 'after'
                            ],
                            [
                                'lable' => $this->trans('afterOn'),
                                'value' => 'afterOn'
                            ],
                        ),
                'datetime' => array(
                            [
                                'lable' => $this->trans('before'),
                                'value' => 'beforeDateTime'
                            ],
                            [
                                'lable' => $this->trans('beforeOn'),
                                'value' => 'beforeDateTimeOn'
                            ],
                            [
                                'lable' => $this->trans('after'),
                                'value' => 'afterDateTime'
                            ],
                            [
                                'lable' => $this->trans('afterOn'),
                                'value' => 'afterDateTimeOn'
                            ],
                        ),
                'time' => array(
                            [
                                'lable' => $this->trans('before'),
                                'value' => 'beforeTime'
                            ],
                            [
                                'lable' => $this->trans('beforeOn'),
                                'value' => 'beforeTimeOn'
                            ],
                            [
                                'lable' => $this->trans('after'),
                                'value' => 'afterTime'
                            ],
                            [
                                'lable' => $this->trans('afterOn'),
                                'value' => 'afterTimeOn'
                            ],
                        ),
                'number' => array(
                            [
                                'lable' => $this->trans('is'),
                                'value' => 'is'
                            ],
                            [
                                'lable' => $this->trans('isNot'),
                                'value' => 'isNot'
                            ],
                            [
                                'lable' => $this->trans('contains'),
                                'value' => 'contains'
                            ],
                            [
                                'lable' => $this->trans('greaterThan'),
                                'value' => 'greaterThan'
                            ],
                            [
                                'lable' => $this->trans('lessThan'),
                                'value' => 'lessThan'
                            ],
                        ),
            ];
    }

    public function getTicketActions($force = false)
    {
        $actionArray =  array(
                        'ticket' => [
                                    'TicketPriority' => $this->trans('action.priority'),
                                    'TicketType' => $this->trans('action.type'),
                                    'TicketStatus' => $this->trans('action.status'),

                                    'tag' => $this->trans('action.tag'),
                                    'note' => $this->trans('action.note'),

                                    'assign_agent' => $this->trans('action.assign_agent'),
                                    'assign_group' => $this->trans('action.assign_group'),
                                    'assign_team' => $this->trans('action.assign_team'),

                                    'mail_agent' => $this->trans('action.mail_agent'),
                                    'mail_group' => $this->trans('action.mail_group'),
                                    'mail_team' => $this->trans('action.mail_team'),
                                    'mail_customer' => $this->trans('action.mail_customer'),

                                    'mail_last_collaborator' => $this->trans('action.mail_last_collaborator'),
                                    'delete_ticket' => $this->trans('action.delete_ticket'),
                                    'mark_spam' => $this->trans('action.mark_spam'),
                                    ],
                        'task'  => [
                                    // 'assign_agent' => $this->trans('action.assign_agent'),
                                    'reply' => $this->trans('action.reply'),
                                    'mail_agent' => $this->trans('action.mail_agent'),
                                    'mail_members' => $this->trans('action.mail_members'),
                                    'mail_last_member' => $this->trans('action.mail_last_member'),
                                    ],
                        'customer'  => [
                                    'mail_customer' => $this->trans('action.mail_customer'),
                                    ],
                        'agent'  => [
                                    'mail_agent' => $this->trans('action.mail_agent'),
                                    'ticket_transfer' => $this->trans('action.ticket_transfer'),
                                    'task_transfer' => $this->trans('action.task_transfer'),
                                    ],
                    );

        $actionRoleArray = [

             'ticket->TicketPriority' => 'ROLE_AGENT_UPDATE_TICKET_PRIORITY',
             'ticket->TicketType'     => 'ROLE_AGENT_UPDATE_TICKET_TYPE',
             'ticket->TicketStatus'   => 'ROLE_AGENT_UPDATE_TICKET_STATUS',
             'ticket->tag'            => 'ROLE_AGENT_ADD_TAG',
             'ticket->note'           => 'ROLE_AGENT_ADD_NOTE',
             'ticket->assign_agent'   => 'ROLE_AGENT_ASSIGN_TICKET',
             'ticket->assign_group'   => 'ROLE_AGENT_ASSIGN_TICKET_GROUP',
             'ticket->assign_team'    => 'ROLE_AGENT_ASSIGN_TICKET_GROUP',
             'ticket->mail_agent'     => 'ROLE_AGENT',
             'ticket->mail_group'     => 'ROLE_AGENT_MANAGE_GROUP',
             'ticket->mail_team'      => 'ROLE_AGENT_MANAGE_SUB_GROUP',
             'ticket->mail_customer'  => 'ROLE_AGENT',
             'ticket->mail_last_collaborator' => 'ROLE_AGENT',
             'ticket->delete_ticket'  => 'ROLE_AGENT_DELETE_TICKET',
             'ticket->mark_spam'      => 'ROLE_AGENT_UPDATE_TICKET_STATUS',

             'task->reply' => 'ROLE_AGENT',
             'task->mail_agent' => 'ROLE_AGENT',
             'task->mail_members' => 'ROLE_AGENT',
             'task->mail_last_member' => 'ROLE_AGENT',

             'customer->mail_customer' => 'ROLE_AGENT',

             'agent->mail_agent' => 'ROLE_AGENT',
             'agent->ticket_transfer' => 'ROLE_AGENT_ASSIGN_TICKET',
             'agent->task_transfer' => 'ROLE_AGENT_EDIT_TASK',
        ];


        $resultArray = [];
        foreach($actionRoleArray as $action => $role) {
            if($role == 'ROLE_AGENT' || $this->container->get('user.service')->checkPermission($role) || $force) {
                $actionPath = explode('->', $action);
                $resultArray[$actionPath[0]][$actionPath[1]] = $actionArray[$actionPath[0]][$actionPath[1]];
            }
        }
        //$repo = $this->container->get('doctrine.orm.entity_manager')->getRepository('WebkulAppBundle:ECommerceChannel');
        //$ecomChannels = $repo->getActiveChannelsByCompany($this->container->get('user.service')->getCurrentCompany());
        $ecomArray= [];

        // foreach($ecomChannels as $channel) {
        //     $ecomArray['add_order_to_' . $channel['id']] = $this->trans('Add order to: ') . $channel['title'];
        // }

        $resultArray['ticket'] = array_merge($resultArray['ticket'], $ecomArray);
        return $resultArray;
    }

    public function trans($text)
    {

        return $this->container->get('translator')->trans($text);
    }

    public function getTicketEvents()
    {
        $events = array(
                        'ticket' => $this->trans('ticket'),
                        'agent' => $this->trans('agent'),
                        'customer' => $this->trans('customer'),
                    );
        if(!$this->container->get('user.service')->getCurrentPlan() || $this->container->get('user.service')->getCurrentPlan()->getTasks())
            $events['task'] = $this->trans('task');
        return $events;
    }

    public function getTicketEventValues()
    {
        return  array(
                        'ticket' => [
                                    'created' => $this->trans('events.created'),
                                    'deleted' => $this->trans('events.deleted'),
                                    'threadUpload' => $this->trans('events.thread.updated'),
                                    'priority' => $this->trans('events.priority'),
                                    'type' => $this->trans('events.type'),
                                    'status' => $this->trans('events.status'),
                                    'group' => $this->trans('events.group'),
                                    'team' => $this->trans('events.team'),
                                    'agent' => $this->trans('events.agent'),
                                    'collaboratorAdded' => $this->trans('events.collaborator.add'),
                                    'note' => $this->trans('events.note'),
                                    'replyCustomer' => $this->trans('events.reply.customer'),
                                    'replyAgent' => $this->trans('events.reply.agent'),
                                    'replyByCollaborator' => $this->trans('events.reply.collaborator'),
                                    ],
                        'task' => [
                                    'created' => $this->trans('events.created'),
                                    'updated' => $this->trans('events.updated'),
                                    'deleted' => $this->trans('events.deleted'),
                                    'memberAdded' => $this->trans('events.member.add'),
                                    'memberRemoved' => $this->trans('events.member.remove'),
                                    'reply' => $this->trans('events.reply'),
                                    ],
                        'agent' => [
                                    'created' => $this->trans('events.created'),
                                    'updated' => $this->trans('events.updated'),
                                    'deleted' => $this->trans('events.deleted'),
                                    'forgotPassword' => $this->trans('events.agent.forgot.password'),
                                    ],
                        'customer' => [
                                    'created' => $this->trans('events.created'),
                                    'updated' => $this->trans('events.updated'),
                                    'deleted' => $this->trans('events.deleted'),
                                    'forgotPassword' => $this->trans('events.customer.forgot.password'),
                                    ],
                    );
    }

    public function getAllSources()
    {
        $sources = ['email' => 'Email', 'website' => 'Website', 'facebook' => 'Facebook', 'twitter' => 'Twitter', 'disqus-engage' => 'Disqus Engage', 'ebay' => 'EBay', 'api' => 'API', 'formbuilder' => 'FormBuilder', 'knock' => 'Binaka', 'mercadolibre' => 'Mercadolibre', 'youtube' => 'Youtube', 'amazon' => 'Amazon'];
        return $sources;
    }

    public function getCustomLabelDetails($container)
    {
        $currentUser = $container->get('user.service')->getCurrentUser();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(DISTINCT t) as ticketCount,sl.id')->from("UVDeskCoreBundle:Ticket", 't')
                ->leftJoin('t.supportLabels','sl')
                ->andwhere('sl.user = :userId')
                ->setParameter('userId', $currentUser->getId())
                ->groupBy('sl.id');

        $ticketCountResult = $qb->getQuery()->getResult();

        $data = array();
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('sl.id,sl.name,sl.colorCode')->from("UVDeskCoreBundle:SupportLabel", 'sl')
                ->andwhere('sl.user = :userId')
                ->setParameter('userId', $currentUser->getId());

        $labels = $qb->getQuery()->getResult();

        foreach ($labels as $key => $label) {
            $labels[$key]['count'] = 0;
            foreach ($ticketCountResult as $ticketCount) {
                if(($label['id'] == $ticketCount['id']))
                    $labels[$key]['count'] = $ticketCount['ticketCount'] ?: 0;
            }
        }

        return $labels;
    }
}

