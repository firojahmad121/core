<?php

namespace Webkul\UVDesk\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Common\Collections\Criteria;
/**
 * AgentPrivilegeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SupportPrivilegeRepository extends \Doctrine\ORM\EntityRepository
{
    public $safeFields = array('page','limit','sort','order','direction');
    const LIMIT = 10;

	public function getAllPrivileges(\Symfony\Component\HttpFoundation\ParameterBag $obj = null, $container) {
    
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('ap')->from($this->getEntityName(), 'ap');

        $data = $obj->all();

        $data = array_reverse($data);
        foreach ($data as $key => $value) {
            if(!in_array($key,$this->safeFields)) {
                if($key!='dateUpdated' AND $key!='dateAdded' AND $key!='search') {
                    $qb->Andwhere('ap.'.$key.' = :'.$key);
                    $qb->setParameter($key, $value);
                } else {
                    if($key == 'search') {
                        $qb->orwhere('ap.name'.' LIKE :name');
                        $qb->setParameter('name', '%'.urldecode($value).'%');    
                        $qb->orwhere('ap.description'.' LIKE :description');
                        $qb->setParameter('description', '%'.urldecode($value).'%');
                    }
                }
            }
        }   

        if(!isset($data['sort'])){
            $qb->orderBy('ap.createdAt',Criteria::DESC);
        }

        $paginator  = $container->get('knp_paginator');

        $results = $paginator->paginate(
            $qb,
            isset($data['page']) ? $data['page'] : 1,
            self::LIMIT,
            array('distinct' => false)
        );

        $paginationData = $results->getPaginationData();
        $queryParameters = $results->getParams();

        $paginationData['url'] = '#'.$container->get('uvdesk.service')->buildPaginationQuery($queryParameters);

        $parsedCollection = array_map(function($privilege) {
            return [
                'id' => $privilege->getId(),
                'name' => $privilege->getName(),
                'description' => $privilege->getDescription(),
            ];
        }, $results->getItems()); 

       
        $json['privileges']         = $parsedCollection;
        $json['pagination_data']    = $paginationData;
      
        return $json;
    }
}
