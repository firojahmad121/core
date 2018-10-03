<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\UVDeskCoreBundle\Entity;
use Webkul\UVDesk\UVDeskCoreBundle\Form;
use Webkul\UVDesk\UVDeskCoreBundle\Entity\User;
use Webkul\UVDesk\UVDeskCoreBundle\Entity\SupportTeam;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class Team extends Controller
{
    public function listTeams(Request $request)
    {
       //$this->isAuthorized('ROLE_AGENT_MANAGE_SUB_GROUP');
       return $this->render('@UVDeskCore/Teams/listSupportTeams.html.twig');
    }

    public function createTeam(Request $request)
    {
        $supportTeam = new SupportTeam;
        $errors = [];

        if($request->getMethod() == "POST") {
           
            $request->request->set('users', explode(',', $request->request->get('tempUsers')));
            $request->request->set('groups', explode(',', $request->request->get('tempGroups')));
            $oldUsers = ($usersList = $supportTeam->getUsers()) ? $usersList->toArray() : $usersList;
            $oldGroups = ($grpList =  $supportTeam->getSupportGroups()) ? $grpList->toArray() : $grpList;

           // $form = $this->createForm(Form\SubGroup::class, $subGroup, ['container' => $this->container,]);
            
            $form = $this->createForm(Form\SubGroup::class, $supportTeam, [
                'container' => $this->container,
            ]);

            $form->handleRequest($request);

            $allDetails = $request->request->all(); 
      
            $em = $this->getDoctrine()->getManager();
            $supportTeam->setName($allDetails['name']);
            $supportTeam->setDescription($allDetails['description']);
            $supportTeam->setIsActive((bool) isset($allDetails['isActive']));
            $em->persist($supportTeam);

            $usersList = (!empty($allDetails['users']))? $allDetails['users'] : [];
            $usersGroup  = (!empty($allDetails['groups']))? $allDetails['groups'] : [];

            if (!empty($usersList)) {
                $usersList = array_map(function ($user) { return 'user.id = ' . $user; }, $usersList);

                $userList = $em->createQueryBuilder()->select('user')
                    ->from('UVDeskCoreBundle:User', 'user')
                    ->where(implode(' OR ', $usersList))
                    ->getQuery()->getResult();
            }

 
            if (!empty($usersGroup)) {
                $usersGroup = array_map(function ($group) { return 'p.id = ' . $group; }, $usersGroup);
            
                $userGroup = $em->createQueryBuilder('p')->select('p')
                    ->from('UVDeskCoreBundle:SupportGroup', 'p')
                    ->where(implode(' OR ', $usersGroup))
                    ->getQuery()->getResult();

            }

            foreach ($userList as $user) {
                $userInstance = $user->getAgentInstance();
                $userInstance->addSupportTeam($supportTeam);
                $em->persist($userInstance);
            }

            // Add Teams to Group
            foreach ($userGroup as $supportGroup) {
                $supportGroup->addSupportTeam($supportTeam);
                $em->persist($supportGroup);
            }
            $em->persist($supportTeam);
            $em->flush();


            return $this->redirect($this->generateUrl('helpdesk_member_support_team_collection'));


            if ($form->isSubmitted() && $form->isValid() && !($errors = $this->customBlankValidation($subGroup, ['groups']))) {

                $em = $this->getDoctrine()->getManager();
                $em->persist($subGroup);

                foreach ($subGroup->getUsers() as $user) {
                    if(!$oldUsers || !in_array($user, $oldUsers)){
                        $user->addSubGroup($subGroup);
                        $em->persist($user);
                    } elseif($oldUsers && ($key = array_search($user, $oldUsers)) !== false)
                        unset($oldUsers[$key]);
                }

                foreach ($oldUsers as $removeUser) {
                    $removeUser->removeSubGroup($subGroup);
                    $em->persist($removeUser);
                }

                foreach ($subGroup->getGroups() as $group) {
                    if(!$oldGroups || !in_array($group, $oldGroups)){
                        $group->addSubGroup($subGroup);
                        $em->persist($group);
                    } elseif($oldGroups && ($key = array_search($group, $oldGroups)) !== false)
                        unset($oldGroups[$key]);
                }

                foreach ($oldGroups as $removeGroup) {
                    $removeGroup->removeSubGroup($subGroup);
                    $em->persist($removeGroup);
                }

                $em->flush();

                if($request->attributes->get('id')) {
                    $this->get('event.manager')->trigger([
                            'event' => 'team.updated',
                            'entity' => $subGroup
                        ]);
                    $message = $this->translate('Success! Team has been updated successfully.');
                } else {
                    $this->get('event.manager')->trigger([
                            'event' => 'team.created',
                            'entity' => $subGroup
                        ]);
                    $message = $this->translate('Success! Team has been added successfully.');
                }

                $this->addFlash('success', $message);

                return $this->redirect($this->generateUrl('helpdesk_member_support_team_collection'));
            } else {
                $errors = array_merge($this->getFormErrors($form), $this->customBlankValidation($subGroup, ['groups']));
                if(isset($errors['groups']))
                    $errors['groups'] = $this->translate('This field is mandatory');
                if(isset($errors['users']))
                    $errors['users'] = $this->translate('This field is mandatory');
            }
        }

        return $this->render('@UVDeskCoreBundle/Teams/createSupportTeam.html.twig', [
                'team' => $supportTeam,
                'errors' => json_encode($errors)
        ]);
    }

    public function editTeam(Request $request)
    {
        if($request->attributes->get('supportTeamId')){
            $supportTeam = $this->getDoctrine()->getRepository('UVDeskCoreBundle:SupportTeam')
                        ->findSubGroupById(['id' => $request->attributes->get('supportTeamId')]);

            if(!$supportTeam)
                $this->noResultFound();
        }
        $errors = [];
        if($request->getMethod() == "POST") {
            $request->request->set('users', explode(',', $request->request->get('tempUsers')));
            $request->request->set('groups', explode(',', $request->request->get('tempGroups')));
            $oldUsers = ($usersList = $supportTeam->getUsers()) ? $usersList->toArray() : $usersList;
            $oldGroups = ($grpList = $supportTeam->getSupportGroups()) ? $grpList->toArray() : $grpList;
           
            $form = $this->createForm(Form\SubGroup::class, $supportTeam, [
                'container' => $this->container,
            ]);

            $form->handleRequest($request);
            $allDetails = $request->request->all(); 
            
            $em = $this->getDoctrine()->getManager();
            $supportTeam->setName($allDetails['name']);
            $supportTeam->setDescription($allDetails['description']);
            $supportTeam->setIsActive((bool) isset($allDetails['isActive']));

            $usersList = (!empty($allDetails['users']))? $allDetails['users'] : [];
            $usersGroup  = (!empty($allDetails['groups']))? $allDetails['groups'] : [];

            if (!empty($usersList)) {
                $usersList = array_map(function ($user) { return 'p.id = ' . $user; }, $usersList);
                $userList = $em->createQueryBuilder('p')->select('p')
                    ->from('UVDeskCoreBundle:User', 'p')
                    ->where(implode(' OR ', $usersList))
                    ->getQuery()->getResult();
            }

            if (!empty($usersGroup)) {
                $usersGroup = array_map(function ($group) { return 'p.id = ' . $group; }, $usersGroup);
            
                $userGroup = $em->createQueryBuilder('p')->select('p')
                    ->from('UVDeskCoreBundle:SupportGroup', 'p')
                    ->where(implode(' OR ', $usersGroup))
                    ->getQuery()->getResult();
            }
 
            foreach ($userList as $user) {
                $userInstance = $user->getAgentInstance();
                if(!$oldUsers || !in_array($userInstance, $oldUsers)){
                    $userInstance->addSupportTeam($supportTeam);
                    $em->persist($userInstance);
                }elseif($oldUsers && ($key = array_search($userInstance, $oldUsers)) !== false)
                     unset($oldUsers[$key]); 
            }
            foreach ($oldUsers as $removeUser) {
                $removeUser->removeSupportTeam($supportTeam);
                $em->persist($removeUser);
            }

            // Add Group to team
            foreach ($userGroup as $supportGroup) {
                if(!$oldGroups || !in_array($supportGroup, $oldGroups)){
                    $supportGroup->addSupportTeam($supportTeam);
                    $em->persist($supportGroup);

                }elseif($oldGroups && ($key = array_search($supportGroup, $oldGroups)) !== false)
                    unset($oldGroups[$key]);
            }
            
            foreach ($oldGroups as $removeGroup) {
                $removeGroup->removeSupportTeam($supportTeam);
                $em->persist($removeGroup);
            }
            
            $em->persist($supportTeam);
            $em->flush();


            return $this->redirect($this->generateUrl('helpdesk_member_support_team_collection'));

            
            if ($form->isSubmitted() && $form->isValid() && !($errors = $this->customBlankValidation($subGroup, ['groups']))) {

                $em = $this->getDoctrine()->getManager();
                $subGroup->setCompany($company);
                $em->persist($subGroup);

                foreach ($subGroup->getUsers() as $user) {
                    if(!$oldUsers || !in_array($user, $oldUsers)){
                        $user->addSubGroup($subGroup);
                        $em->persist($user);
                    } elseif($oldUsers && ($key = array_search($user, $oldUsers)) !== false)
                        unset($oldUsers[$key]);
                }

                foreach ($oldUsers as $removeUser) {
                    $removeUser->removeSubGroup($subGroup);
                    $em->persist($removeUser);
                }

                foreach ($subGroup->getGroups() as $group) {
                    if(!$oldGroups || !in_array($group, $oldGroups)){
                        $group->addSubGroup($subGroup);
                        $em->persist($group);
                    } elseif($oldGroups && ($key = array_search($group, $oldGroups)) !== false)
                        unset($oldGroups[$key]);
                }

                foreach ($oldGroups as $removeGroup) {
                    $removeGroup->removeSubGroup($subGroup);
                    $em->persist($removeGroup);
                }

                $em->flush();

                if($request->attributes->get('id')) {
                    $this->get('event.manager')->trigger([
                            'event' => 'team.updated',
                            'entity' => $subGroup
                        ]);
                    $message = $this->translate('Success! Team has been updated successfully.');
                } else {
                    $this->get('event.manager')->trigger([
                            'event' => 'team.created',
                            'entity' => $subGroup
                        ]);
                    $message = $this->translate('Success! Team has been added successfully.');
                }

                $this->addFlash('success', $message);

                return $this->redirect($this->generateUrl('helpdesk_member_support_team_collection'));
            } else {
                $errors = array_merge($this->getFormErrors($form), $this->customBlankValidation($subGroup, ['groups']));
                if(isset($errors['groups']))
                    $errors['groups'] = $this->translate('This field is mandatory');
                if(isset($errors['users']))
                    $errors['users'] = $this->translate('This field is mandatory');
            }
        } 
        return $this->render('@UVDeskCore/Teams/updateSupportTeam.html.twig', [
                'team' => $supportTeam,
                'errors' => json_encode($errors)
        ]);
    }
}
