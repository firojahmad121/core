<?php

namespace Webkul\UVDesk\CoreBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\Common\Collections;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;

class EmailTemplatesRepository extends EntityRepository
{
	public $safeFields = array('page','limit','sort','order','direction');
    const LIMIT = 10;

    public function getTemplates(\Symfony\Component\HttpFoundation\ParameterBag $obj = null, $container) {
        $user_id = $container->get('security.token_storage')->getToken()->getUser()->getId();
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('sr')->from($this->getEntityName(), 'sr')
            ->orWhere('sr.user IS NULL ')
            ->orWhere('sr.user='.$user_id);

        $data = $obj->all();
        $data = array_reverse($data);
        foreach ($data as $key => $value) {
            if(!in_array($key,$this->safeFields)) {
                if($key!='dateUpdated' AND $key!='dateAdded' AND $key!='search') {
                    $qb->andwhere('sr.'.$key.' = :'.$key);
                    $qb->setParameter($key, $value);
                } else {
                    if($key == 'search') {
                        $qb->andwhere('sr.name'.' LIKE :name');
                        $qb->setParameter('name', '%'.urldecode($value).'%');    
                    }
                }
            }
        }   
        
        if(!isset($data['sort']))
            $qb->orderBy('sr.id', Criteria::DESC);

        $paginator  = $container->get('knp_paginator');

        $newQb = clone $qb;
        $newQb->select('COUNT(DISTINCT sr.id)');

        $results = $paginator->paginate(
            $qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->setHint('knp_paginator.count', $newQb->getQuery()->getSingleScalarResult()),
            isset($data['page']) ? $data['page'] : 1,
            self::LIMIT,
            array('distinct' => false)
        );

        $paginationData = $results->getPaginationData();
        $queryParameters = $results->getParams();
        if(isset($queryParameters['template']))
            unset($queryParameters['template']);

        $paginationData['url'] = '#'.$container->get('uvdesk.service')->buildPaginationQuery($queryParameters);

        $json['templates'] = $results->getItems();
        $json['pagination_data'] = $paginationData;

        return $json;
    }

	public function getSavedReplies(\Symfony\Component\HttpFoundation\ParameterBag $obj = null, $container) {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT sr.id, sr.name')->from($this->getEntityName(), 'sr');

        $data = $obj->all();
        $data = array_reverse($data);
        foreach ($data as $key => $value) {
            if(!in_array($key,$this->safeFields)) {
                if($key!='dateUpdated' AND $key!='dateAdded' AND $key!='search') {
                    $qb->andwhere('sr.'.$key.' = :'.$key);
                    $qb->setParameter($key, $value);
                } else {
                    if($key == 'search') {
                        $qb->andwhere('sr.name'.' LIKE :name');
                        $qb->setParameter('name', '%'.urldecode($value).'%');    
                    }
                }
            }
        }   

        $qb->andwhere('sr.company'.' = :company')
            ->setParameter('company', $container->get('user.service')->getCurrentCompany()->getId());
        $this->addGroupTeamFilter($qb, $container);            

        if(!isset($data['sort']))
            $qb->orderBy('sr.id', Criteria::DESC);

        $paginator  = $container->get('knp_paginator');

        $newQb = clone $qb;
        $newQb->select('COUNT(DISTINCT sr.id)');

        $results = $paginator->paginate(
            $qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->setHint('knp_paginator.count', $newQb->getQuery()->getSingleScalarResult()),
            isset($data['page']) ? $data['page'] : 1,
            self::LIMIT,
            array('distinct' => false)
        );

        $paginationData = $results->getPaginationData();
        $queryParameters = $results->getParams();
        if(isset($queryParameters['template']))
            unset($queryParameters['template']);

        $paginationData['url'] = '#'.$container->get('default.service')->symfony_http_build_query($queryParameters);

        $json['savedReplies'] = $results->getItems();
        $json['pagination_data'] = $paginationData;
       
        return $json;
    }

    public function getSavedReply( $id , $container)
    {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('sr')->from($this->getEntityName(), 'sr')
            ->andWhere('sr.id = :id')
            ->setParameter('id', $id )
            ->andwhere('sr.company = :company')
            ->setParameter('company', $container->get('user.service')->getCurrentCompany()->getId());

        $this->addGroupTeamFilter($qb, $container);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function addGroupTeamFilter($qb, $container, $entityAlias = 'sr')
    {
        $qb->leftJoin($entityAlias.'.groups', 'grps')
            ->leftJoin($entityAlias.'.teams', 'tms');

        $user = $container->get('user.service')->getCurrentUser();
        $userCondition = $qb->expr()->orX();
        $userCondition->add($qb->expr()->eq($entityAlias.'.user', ':userId'));
        $qb->setParameter('userId', $container->get('user.service')->getCurrentUser()->getDetail()['agent']->getId());
        
        if($user->getGroups()) {
            foreach($user->getGroups() as $key => $grp) {
                $userCondition->add($qb->expr()->eq('grps.id', ':groupId'.$key));
                $qb->setParameter('groupId'.$key, $grp->getId());
            }
        }
        $subgroupIds = $container->get('user.service')->getCurrentUserSubGroupIds();
        foreach($subgroupIds as $key => $teamId) {
            $userCondition->add($qb->expr()->eq('tms.id', ':teamId'.$key ));
            $qb->setParameter('teamId'.$key, $teamId);
        } 
        $qb->andWhere($userCondition);

        return $qb;        
    }
}
