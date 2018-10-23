<?php

namespace Webkul\UVDesk\CoreBundle\Workflow\Actions\Agent;

use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;

class MailAgent extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.agent.mail_agent';
    }

    public static function getDescription()
    {
        return 'Mail to agent';
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::AGENT;
    }
    
    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        dump($entityManager);
        die;

        $results = $this->getDoctrine()
                        ->getRepository('UVDeskCoreBundle:EmailTemplates')
                        ->findAll();
                    
        $emailTemplates = $json = [];
        foreach ($results as $key => $result) {
            $emailTemplates[] = [
                'id' => $result->getId(),
                'name' => $result->getName(),
            ];
        }
        
        $agentResults = $this->container->get('user.service')->getAgentsPartialDetails();
        $partResults[] = ['id' => $userId = $this->getUser()->getId(), 'name' => $this->trans('me')];
        $partResults[] = ['id' => 'responsePerforming', 'name' => $this->trans('action.responsePerforming.agent')];
        if(in_array($request->attributes->get('event'), ['ticket', 'task'])){
            $partResults[] = ['id' => 'assignedAgent', 'name' => $this->trans('action.assign.agent')];
        }elseif(in_array($request->attributes->get('event'), ['agent'])){
            $partResults[] = ['id' => 'baseAgent', 'name' => $this->trans('action.created.agent')];
        }
        foreach ($agentResults as $key => $agentResult) {
            if($userId != $agentResult['id'])
                $partResults[] = [
                            'id' => $agentResult['id'],
                            'name' => $agentResult['name'],
                            ];
        }
        
        if(in_array($request->attributes->get('entity'), ['mail_agent', 'mail_group', 'mail_team'])){
            $json['templates'] = $emailTemplates;
            $json['partResults'] = $partResults;
            $json = json_encode($json);
        }else
            $json = json_encode($emailTemplates);
        $results = [];
    }

    public static function applyAction(ContainerInterface $container, $entity, $value = null)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
    }
}
