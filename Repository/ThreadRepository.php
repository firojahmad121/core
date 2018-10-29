<?php

namespace Webkul\UVDesk\CoreBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Collections\Criteria;
use Webkul\UVDesk\CoreBundle\Entity\User;
use Webkul\UVDesk\CoreBundle\Entity\Ticket;
/**
 * ThreadRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ThreadRepository extends \Doctrine\ORM\EntityRepository
{
    const DEFAULT_PAGINATION_LIMIT = 15;
    const LIMIT = 10;

    public function findTicketBySubject($email, $subject) {
        if (stripos($subject,"RE: ") !== false) {
            $subject = str_ireplace("RE: ", "", $subject);
        }

        if (stripos($subject,"FWD: ") !== false) {
            $subject = str_ireplace("FWD: ","",$subject);
        }

        $ticket = $this->getEntityManager()->createQuery("SELECT t FROM UVDeskCoreBundle:Ticket t WHERE t.subject LIKE :referenceIds" )
            ->setParameter('referenceIds', '%' . $subject . '%')
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return ($ticket && strtolower($ticket->getCustomer()->getEmail()) == strtolower($email)) ? $ticket : null;
    }

    public function prepareBasePaginationRecentThreadsQuery($ticket, array $params, $enabledLockedThreads = true)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select("thread, user")
            ->from('UVDeskCoreBundle:Thread', 'thread')
            ->leftJoin('thread.user', 'user')
            ->where('thread.ticket = :ticket')->setParameter('ticket', $ticket)
            ->andWhere('thread.threadType != :disabledThreadType')->setParameter('disabledThreadType', 'create')
            ->orderBy('thread.id', Criteria::DESC);

        // Filter locked threads
        if (false === $enabledLockedThreads) {
            $queryBuilder->andWhere('thread.isLocked = :isThreadLocked')->setParameter('isThreadLocked', false);
        }

        // Filter threads by their type
        switch (!empty($params['threadType']) ? $params['threadType'] : 'reply') {
            case 'reply':
                $queryBuilder->andWhere('thread.threadType = :threadType')->setParameter('threadType', 'reply');
                break;
            case 'forward':
                $queryBuilder->andWhere('thread.threadType = :threadType')->setParameter('threadType', 'forward');
                break;
            case 'note':
                $queryBuilder->andWhere('thread.threadType = :threadType')->setParameter('threadType', 'note');
                break;
            case 'bookmark':
            case 'pinned':
                $queryBuilder->andWhere('thread.isBookmarked = :isBookmarked')->setParameter('isBookmarked', true);
                break;
            case 'task':
                // $queryBuilder->andWhere('thread.threadType = :threadType')->setParameter('threadType', 'forward');
                break;
            default:
                break;
        }

        return $queryBuilder;
    }

    public function getAllCustomerThreads($ticketId,\Symfony\Component\HttpFoundation\ParameterBag $obj = null, $container) {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select("th, u.id as userId, CONCAT(u.firstName, ' ', u.lastName) as fullname, userInstance.profileImagePath as smallThumbnail")->from($this->getEntityName(), 'th')
            ->leftJoin('th.user', 'u')
            ->leftJoin('u.userInstance', 'userInstance')
            ->andwhere('th.threadType = :threadType')
            ->setParameter('threadType', 'reply')
            ->andwhere('th.ticket = :ticketId')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('th.id', 'DESC');

        $data = $obj->all();

        $newQb = clone $qb;
        $newQb->select('COUNT(DISTINCT th.id)');
        $paginator = $container->get('knp_paginator');
        $results = $paginator->paginate(
            $qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->setHint('knp_paginator.count', $newQb->getQuery()->getSingleScalarResult()),
            isset($data['page']) ? $data['page'] : 1,
            self::LIMIT,
            array('distinct' => false)
        );

        $paginationData = $results->getPaginationData();
        $queryParameters = $results->getParams();

        $queryParameters['page'] = "replacePage";
        $paginationData['url'] = '#'.$container->get('uvdesk.service')->buildPaginationQuery($queryParameters);

        $data = array();
        $userService = $container->get('user.service');
       // dump($results->getItems());
        foreach ($results->getItems() as $key => $row) {
            $thread = $row[0];
            $data[] = [
                'id' => $thread['id'],
                'user' => $row['userId'] ? ['id' => $row['userId'], 'smallThumbnail' => $row['smallThumbnail']] : null,
                'fullname' => $row['fullname'],
                'reply' => strip_tags($thread['message']),
                'source' => $thread['source'],
                'threadType' => $thread['threadType'],
                'userType' => 'customer',
                'formatedCreatedAt' => date_format($thread['createdAt'],"m-d-y h:i:s A"),
                'timestamp' => $userService->convertToDatetimeTimezoneTimestamp($thread['createdAt']),
                'cc' => $thread['cc'],
                'bcc' => $thread['bcc'],
                'attachments' => [],

            ];
        }
        
        $json['threads'] = $data;
        $json['pagination'] = $paginationData;

        return $json;
    }
}
