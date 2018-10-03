<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity;
use Webkul\UVDesk\CoreBundle\Form;
use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Entity\SupportGroup;
use Webkul\UVDesk\CoreBundle\Entity\SupportTeam;
use Webkul\UVDesk\CoreBundle\Entity\UserInstance;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Group extends Controller
{
    public function listGroups(Request $request)
    {
        return $this->render('@UVDeskCore/Groups/listSupportGroups.html.twig');
    }

    public function editGroup(Request $request)
    {
        if($request->attributes->get('supportGroupId')){
            $group = $this->getDoctrine()->getRepository('UVDeskCoreBundle:SupportGroup')
                ->findGroupById(['id' => $request->attributes->get('supportGroupId'),
            ]); 
                            
            if(!$group)
                $this->noResultFound();
        } else
            $group = new Entity\SupportGroup;

        $errors = [];
        if($request->getMethod() == "POST") {
            $data = $request->request->all() ? : json_decode($request->getContent(), true);
            $request->request->replace($data); // also for api

            if($request->request->get('tempUsers'))
                $request->request->set('users', explode(',', $request->request->get('tempUsers')));

            if($request->request->get('tempTeams'))
                $request->request->set('supportTeams', explode(',', $request->request->get('tempTeams')));

            $oldUsers = ($usersList = $group->getUsers()) ? $usersList->toArray() : [];
            $oldTeam  = ($teamList = $group->getSupportTeams()) ? $teamList->toArray() : [];
            
            $form = $this->createForm(Form\Group::class, $group, [
                'container' => $this->container,
            ]);

            $allDetails = $request->request->all();
            
            $em = $this->getDoctrine()->getManager();
            $group->setName($allDetails['name']);
            $group->setDescription($allDetails['description']);
            $group->setIsActive((bool) isset($allDetails['isActive']));
                    
            $usersList = (!empty($allDetails['users']))? $allDetails['users'] : [];
            $userTeam  = (!empty($allDetails['supportTeams']))? $allDetails['supportTeams'] : [];

            if (!empty($usersList)) {
                $usersList = array_map(function ($user) { return 'user.id = ' . $user; }, $usersList);

                $userList = $em->createQueryBuilder()->select('user')
                    ->from('UVDeskCoreBundle:User', 'user')
                    ->where(implode(' OR ', $usersList))
                    ->getQuery()->getResult();
            }

            if (!empty($userTeam)) {
                $userTeam = array_map(function ($team) { return 'team.id = ' . $team; }, $userTeam);

                $userTeam = $em->createQueryBuilder()->select('team')
                    ->from('UVDeskCoreBundle:SupportTeam', 'team')
                    ->where(implode(' OR ', $userTeam))
                    ->getQuery()->getResult();
            }
            if(!empty($userList)){
                // Add Users to Group
                foreach ($userList as $user) {
                    $userInstance = $user->getAgentInstance();               
                    if(!$oldUsers || !in_array($userInstance, $oldUsers)){
                        $userInstance->addSupportGroup($group);
                        $em->persist($userInstance);
                    }elseif($oldUsers && ($key = array_search($userInstance, $oldUsers)) !== false)
                        unset($oldUsers[$key]);
                }
                foreach ($oldUsers as $removeUser) {
                    $removeUser->removeSupportGroup($group);
                    $em->persist($removeUser);
                }
            }else{
                foreach ($oldUsers as $removeUser) {
                    $removeUser->removeSupportGroup($group);
                    $em->persist($removeUser);
                }
            }
            if(!empty($userTeam)){
                // Add Teams to Group
                foreach ($userTeam as $supportTeam) {
            
                    if(!$oldTeam || !in_array($supportTeam, $oldTeam)){
                        $group->addSupportTeam($supportTeam);
                    }elseif($oldTeam && ($key = array_search($supportTeam, $oldTeam)) !== false)
                    unset($oldTeam[$key]);
                }
                foreach ($oldTeam as $removeTeam) {
                    $group->removeSupportTeam($removeTeam);
                    $em->persist($group);
                }
            }else{
                foreach ($oldTeam as $removeTeam) {
                    $group->removeSupportTeam($removeTeam);
                    $em->persist($group);
                }
            }
            $em->persist($group);
            $em->flush();


            return $this->redirect($this->generateUrl('helpdesk_member_support_group_collection'));
        }
        return $this->render('@UVDeskCore/Groups/updateSupportGroup.html.twig', [
                'group' => $group,
                'errors' => json_encode($errors)
            ]);

    }

    public function createGroup(Request $request)
    {
        $group = new SupportGroup;
        $errors = [];
        if($request->getMethod() == "POST") {
            $data = $request->request->all() ? : json_decode($request->getContent(), true);
            $request->request->replace($data); // also for api
            if($request->request->get('tempUsers'))
                $request->request->set('users', explode(',', $request->request->get('tempUsers')));
              
            if($request->request->get('tempTeams'))
                $request->request->set('supportTeams', explode(',', $request->request->get('tempTeams')));
            $oldUsers = ($usersList = $group->getUsers()) ? $usersList->toArray() : [];


            $allDetails = $request->request->all();
           
            $em = $this->getDoctrine()->getManager();
            $group->setName($allDetails['name']);
            $group->setDescription($allDetails['description']);
            $group->setIsActive((bool) isset($allDetails['isActive']));


            $usersList = (!empty($allDetails['users']))? $allDetails['users'] : [];
            $userTeam  = (!empty($allDetails['supportTeams']))? $allDetails['supportTeams'] : [];

            if (!empty($usersList)) {
                $usersList = array_map(function ($user) { return 'user.id = ' . $user; }, $usersList);

                $userList = $em->createQueryBuilder()->select('user')
                    ->from('UVDeskCoreBundle:User', 'user')
                    ->where(implode(' OR ', $usersList))
                    ->getQuery()->getResult();
            }

            if (!empty($userTeam)) {
                $userTeam = array_map(function ($team) { return 'team.id = ' . $team; }, $userTeam);

                $userTeam = $em->createQueryBuilder()->select('team')
                    ->from('UVDeskCoreBundle:SupportTeam', 'team')
                    ->where(implode(' OR ', $userTeam))
                    ->getQuery()->getResult();
            }


            foreach ($userList as $user) {
                $userInstance = $user->getAgentInstance();
                $userInstance->addSupportGroup($group);
                $em->persist($userInstance);
            }

            // Add Teams to Group
            foreach ($userTeam as $supportTeam) {
                $group->addSupportTeam($supportTeam);
            }
            
            $em->persist($group);
            $em->flush();


            // foreach ($userList as $user) {
            //     $userInstance = $user->getAgentInstance();
            //     $userInstance->addSupportGroup($group);
            //     // dump($userInstance->getSupportGroups()->toArray());
            //     // die;
            //     $em->persist($userInstance);
            // }

            // //Add Teams to Group
            // foreach ($userTeam as $supportTeam) {
            //     $group->addSupportTeam($supportTeam);
            // }
           
            return $this->redirect($this->generateUrl('helpdesk_member_support_group_collection'));


            // $usersList = (!empty($allDetails['users']))? $allDetails['users'] : [];
            // $userTeam  = (!empty($allDetails['supportTeams']))? $allDetails['supportTeams'] : [];
            // if(!empty($usersList)){
            //     foreach ($usersList as $user) {
            //             $userInstance = $em->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(['user' => $user]);
            //             $group       = $em->getRepository('UVDeskCoreBundle:SupportGroup')->find(['id' => $supportGroup->getId()]);
            
            //             if(!$oldUsers || !in_array($user, $oldUsers)) {
            //                 $userInstance->addSupportGroup($group);
            //                 $em->persist($userInstance);
            //             }elseif($oldUsers && ($key = array_search($user, $oldUsers)) !== false)
            //                 unset($oldUsers[$key]);

            //             // foreach($userTeam as $team){
            //             //     if(!$oldUsers || !in_array($user, $oldUsers)) {
            //             //         $supportTeam = $em->getRepository('UVDeskCoreBundle:SupportTeam')->find(['id' => $team]);
            //             //         $group->addSupportTeam($supportTeam);
            //             //         $em->persist($group);
            //             //     }elseif($oldUsers && ($key = array_search($user, $oldUsers)) !== false)
            //             //         unset($oldUsers[$key]);
            //             // }
                  
            //     }
            //     foreach ($oldUsers as $removeUser) {
            //         $removeUser->removeSupportGroup($group);
            //         $em->persist($removeUser);
            //     }
            // }  
            // $em->flush();    

            return $this->redirect($this->generateUrl('helpdesk_member_support_group_collection'));
            
            if ($form->isSubmitted() && $form->isvalid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($group);
                $usersList = $group->getUsers();
                
                foreach ($usersList as $user) {
                    if(!$oldUsers || !in_array($user, $oldUsers)) {
                        $user->addGroup($group);
                        $em->persist($user);
                    }elseif($oldUsers && ($key = array_search($user, $oldUsers)) !== false)
                        unset($oldUsers[$key]);
                }

                foreach ($oldUsers as $removeUser) {
                    $removeUser->removeGroup($group);
                    $em->persist($removeUser);
                }
                $em->flush();

                if($request->attributes->get('id')) {
                    $this->get('event.manager')->trigger([
                            'event' => 'group.updated',
                            'entity' => $group
                        ]);
                    $message = $this->translate('Success! Group has been updated successfully.');
                } else {
                    $this->get('event.manager')->trigger([
                            'event' => 'group.created',
                            'entity' => $group
                        ]);
                    $message = $this->translate('Success! Group has been added successfully.');
                }

                $this->addFlash('success', $message);

                return $this->redirect($this->generateUrl('helpdesk_member_support_group_collection'));
            } else {
                $errors = $this->getFormErrors($form);
            }
        }

        return $this->render('@UVDeskCore/Groups/createSupportGroup.html.twig', [
                'group' => $group,
                'errors' => json_encode($errors)
            ]);
    }
}
