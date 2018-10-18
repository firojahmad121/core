<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity\User;
use Webkul\UVDesk\CoreBundle\Entity\UserInstance;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreBundle\Form\UserAccount;
use Webkul\UVDesk\CoreBundle\Form\UserProfile;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class Account extends AbstractController
{
    public function loadDashboard(Request $request)
    {
        return $this->render('@UVDeskCore//dashboard.html.twig', []);
    }

    public function loadProfile(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $originalUser = clone $user;
        $errors = [];

        if($request->getMethod() == "POST") {
            $data     = $request->request->all();
            $dataFiles = $request->files->get('user_form');
           
            $data = $data['user_form'];
            $checkUser = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $data['email']));
           
            $errorFlag = 0;
            if($checkUser) {
                if($checkUser->getId() != $user->getId())
                    $errorFlag = 1;
            }
            if(!$errorFlag) {
                $password = $user->getPassword();

              
                $form = $this->createForm(UserProfile::class, $user);
                $form->handleRequest($request);
                $form->submit(true);
                $encodedPassword = $this->container->get('security.password_encoder')->encodePassword($user, $data['password']['first']);
                if ($form->isValid()) {
                   
                    if($data != null) {

                        if(!empty($encodedPassword) ) {
                            $user->setPassword($encodedPassword);
                        } else {
                            $this->addFlash(
                                'warning',
                                'Error! Given current password is incorrect.'
                            );
                            return $this->redirect($this->generateUrl('helpdesk_member_profile'));
                        }
                    } else {
                        $user->setPassword($password);
                    }
                   
                    $user->setFirstName($data['firstName']);
                    $user->setLastName($data['lastName']);
                    $user->setEmail($data['email']);
                    $em->persist($user);
                    $em->flush();
                    $userInstance = $em->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(array('user' => $user->getId()));

                    $userInstance = $this->container->get('user.service')->getUserDetailById($user->getId());

                    if(isset($dataFiles['profileImage'])){
                        $fileName  = $this->container->get('uvdesk.service')->getFileUploadManager()->upload($dataFiles['profileImage']);
                        $userInstance->setProfileImagePath($fileName);
                    }
                    $userInstance  = $userInstance->setContactNumber($data['contactNumber']);
                    $userInstance  = $userInstance->setSignature($data['signature']);
                    $em->persist($userInstance);
                    $em->flush();

                    $this->addFlash('success','Success ! Profile updated successfully.');
                    return $this->redirect($this->generateUrl('helpdesk_member_profile'));

                } else {
                    $errors = $form->getErrors();
                    dump($errors);
                    die;
                    $errors = $this->getFormErrors($form);
                }
            } else {
                $this->addFlash('warning',('Error ! User with same email is already exist.'));
                return $this->redirect($this->generateUrl('helpdesk_member_profile'));
            }
        }
        return $this->render('@UVDeskCore//profile.html.twig', array(
            'user' => $user,
            'errors' => json_encode($errors)
        ));
    }

    public function editAgent($agentId)
    {
        $em = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();
       
        $activeUser = $this->get('user.service')->getSessionUser();
        $user = $em->getRepository('UVDeskCoreBundle:User')->find($agentId);
        $instanceRole = $user->getAgentInstance()->getSupportRole()->getCode();
     
        if (empty($user)) {
            dump('Not found');die;
        }

        switch (strtoupper($request->getMethod())) {
            case 'POST':
                $formErrors = [];
                $data      = $request->request->get('user_form');
                $dataFiles = $request->files->get('user_form');
                
                $checkUser = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email'=> $data['email']));

                $errorFlag = 0;
                if($checkUser) {
                    if($checkUser->getId() != $agentId)
                        $errorFlag = 1;
                }
                if(!$errorFlag) {
                        if(isset($data['password']) && $data['password'])
                             $user->setPassword($this->encodePassword($user, '12345678'));

                        $user->setFirstName($data['firstName']);
                        $user->setLastName($data['lastName']);
                        $user->setEmail($data['email']);
                        $user->setIsEnabled(isset($data['isActive'])? 1 : 0);

                        $userInstance = $em->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(['user' => $agentId]);

                        $oldSupportTeam = ($supportTeamList = $userInstance->getSupportTeams()) ? $supportTeamList->toArray() : [];
                        $oldSupportGroup  = ($supportGroupList = $userInstance->getSupportGroups()) ? $supportGroupList->toArray() : [];
                        $oldSupportedPrivilege = ($supportPrivilegeList = $userInstance->getSupportPrivileges())? $supportPrivilegeList->toArray() : [];
                        

                        if(isset($data['role'])) {
                            $role = $em->getRepository('UVDeskCoreBundle:SupportRole')->findOneBy(array('code' => $data['role']));
                            $userInstance->setSupportRole($role);
                        }

                        $userInstance->setDesignation($data['designation']);
                        $userInstance->setContactNumber($data['contactNumber']);
                        $userInstance->setSource('website');
                        if(isset($dataFiles['profileImage'])){
                            $fileName  = $this->container->get('uvdesk.service')->getFileUploadManager()->upload($dataFiles['profileImage']);
                            $userInstance->setProfileImagePath($fileName);
                        }
                        $userInstance->setSignature($data['signature']);
                        $isActive = isset($data['isActive']) ? 1 : 0;
                        $userInstance->setIsActive($isActive);
                        $userInstance->setIsVerified(0);

                        if(isset($data['userSubGroup'])){
                            foreach ($data['userSubGroup'] as $userSubGroup) {
                                if($userSubGrp = $this->get('uvdesk.service')->getEntityManagerResult(
                                            'UVDeskCoreBundle:SupportTeam',
                                            'findOneBy', [
                                                'id' => $userSubGroup
                                            ]
                                        )
                                )
                                if(!$oldSupportTeam || !in_array($userSubGrp, $oldSupportTeam)){
                                    $userInstance->addSupportTeam($userSubGrp);
                                   
                                }elseif($oldSupportTeam && ($key = array_search($userSubGrp, $oldSupportTeam)) !== false)
                                    unset($oldSupportTeam[$key]);

                            }

                            foreach ($oldSupportTeam as $removeteam) {
                                $userInstance->removeSupportTeam($removeteam);
                                $em->persist($userInstance);
                            }

                        }

                        if(isset($data['groups'])){
                            foreach ($data['groups'] as $userGroup) {
                                if($userGrp = $this->get('uvdesk.service')->getEntityManagerResult(
                                            'UVDeskCoreBundle:SupportGroup',
                                            'findOneBy', [
                                                'id' => $userGroup
                                            ]
                                        )
                                )

                                if(!$oldSupportGroup || !in_array($userGrp, $oldSupportGroup)){
                                    $userInstance->addSupportGroup($userGrp);
                                   
                                }elseif($oldSupportGroup && ($key = array_search($userGrp, $oldSupportGroup)) !== false)
                                    unset($oldSupportGroup[$key]);
                            }

                            foreach ($oldSupportGroup as $removeGroup) {
                                $userInstance->removeSupportGroup($removeGroup);
                                $em->persist($userInstance);
                            }
                        }

                        if(isset($data['agentPrivilege'])){
                            foreach ($data['agentPrivilege'] as $supportPrivilege) {
                                if($supportPlg = $this->get('uvdesk.service')->getEntityManagerResult(
                                            'UVDeskCoreBundle:SupportPrivilege',
                                            'findOneBy', [
                                                'id' => $supportPrivilege
                                            ]
                                        )
                                )
                                if(!$oldSupportedPrivilege || !in_array($supportPlg, $oldSupportedPrivilege)){
                                    $userInstance->addSupportPrivilege($supportPlg);
                                   
                                }elseif($oldSupportedPrivilege && ($key = array_search($supportPlg, $oldSupportedPrivilege)) !== false)
                                    unset($oldSupportedPrivilege[$key]);  
                            }
                            foreach ($oldSupportedPrivilege as $removeGroup) {
                                $userInstance->removeSupportPrivilege($removeGroup);
                                $em->persist($userInstance);
                            }
                        }

                        $userInstance->setUser($user);
                        $user->addUserInstance($userInstance);
                        $em->persist($user);
                        $em->persist($userInstance);
                        $em->flush();

                        $this->addFlash('success',('Success ! Agent information updated successfully.'));
                        return $this->redirect($this->generateUrl('helpdesk_member_account_collection'));
                    }else {
                        $this->addFlash('warning',('Error ! User with same email already exist.'));
                    }

                $response = $this->render('@UVDeskCore/Agents/updateSupportAgent.html.twig', [
                    'user' => $user,
                    'instanceRole' => $instanceRole,
                    'errors' => json_encode([])
                ]);
                break;
            default:
                $response = $this->render('@UVDeskCore/Agents/updateSupportAgent.html.twig', [
                    'user'         => $user,
                    'instanceRole' => $instanceRole,
                    'errors'       => json_encode([])
                ]);
                break;
        }
        return $response;
    }

    public function listAgents(Request $request)
    {
        return $this->render('@UVDeskCore/Agents/listSupportAgents.html.twig', []);
    }

    public function createAgent(Request $request)
    {
        $user = new User();
        $em = $this->getDoctrine()->getManager();
        $userServiceContainer = $this->get('user.service');
            
        if ('POST' == $request->getMethod()) {

            $data      = $request->request->get('user_form');
            $dataFiles = $request->files->get('user_form');

            if (empty($data['email'])) {
                $user = new User();
            } else {
                $user = $em->getRepository('UVDeskCoreBundle:User')->findOneByEmail($data['email']) ?: new User();
            }

                $userChk = $em->getRepository('UVDeskCoreBundle:User')->getAgentByEmail($data['email']);
                if(!$userChk) {


                    if(isset($data['password']) && $data['password'])
                    $user->setPassword($this->encodePassword($user, '12345678'));

                    $user->setFirstName($data['firstName']);
                    $user->setLastName($data['lastName']);
                    $user->setEmail($data['email']);
                    $user->setIsEnabled(isset($data['isActive'])? 1 : 0);

                    $userInstance = new UserInstance;
                    if(isset($data['role'])) {
                        $role = $em->getRepository('UVDeskCoreBundle:SupportRole')->findOneBy(array('code' => $data['role']));
                        $userInstance->setSupportRole($role);
                    }

                    if(isset($data['ticketView']))
                        $userInstance->setTicketView($data['ticketView']);

                    $userInstance->setDesignation($data['designation']);
                    $userInstance->setContactNumber($data['contactNumber']);
                    $userInstance->setSource('website');
                    
                    if(isset($dataFiles['profileImage'])){
                        $fileName  = $this->container->get('uvdesk.service')->getFileUploadManager()->upload($dataFiles['profileImage']);
                        $userInstance->setProfileImagePath($fileName);
                    }
                    $userInstance->setSignature($data['signature']);

                    $isActive = isset($data['isActive']) ? 1 : 0;
                    $userInstance->setIsActive($isActive);
                    $userInstance->setIsVerified(0);

                    if(isset($data['userSubGroup'])){
                        foreach ($data['userSubGroup'] as $userSubGroup) {
                            if($userSubGrp = $this->get('uvdesk.service')->getEntityManagerResult(
                                        'UVDeskCoreBundle:SupportTeam',
                                        'findOneBy', [
                                            'id' => $userSubGroup
                                        ]
                                    )
                            )

                            $userInstance->addSupportTeam($userSubGrp);
                        }
                    }
                        
                    if(isset($data['groups'])){
                        foreach ($data['groups'] as $userGroup) {
                            if($userGrp = $this->get('uvdesk.service')->getEntityManagerResult(
                                        'UVDeskCoreBundle:SupportGroup',
                                        'findOneBy', [
                                            'id' => $userGroup
                                        ]
                                    )
                            )
                            $userInstance->addSupportGroup($userGrp);
                        }
                    }
                    
                    if(isset($data['agentPrivilege'])){
                        foreach($data['agentPrivilege'] as $supportPrivilege) {
                            if($supportPlg = $this->get('uvdesk.service')->getEntityManagerResult(
                                        'UVDeskCoreBundle:SupportPrivilege',
                                        'findOneBy', [
                                            'id' => $supportPrivilege
                                        ]
                                    )
                            )
                            $userInstance->addSupportPrivilege($supportPlg);
                        }
                    } 
                    $userInstance->setUser($user);
                    $user->addUserInstance($userInstance);
                    $em->persist($user);
                    $em->persist($userInstance);
                    $em->flush();

                    $this->addFlash('success',('Success ! Agent information saved successfully.'));

                    return $this->redirect($this->generateUrl('helpdesk_member_account_collection'));
                } else {

                    $this->addFlash('warning',('Error ! User with same email already exist.'));
                }
        }
        return $this->render('@UVDeskCore/Agents/createSupportAgent.html.twig', [
            'user' => $user,
            'errors' => json_encode([])
        ]);
    }
    protected function encodePassword(User $user, $plainPassword)
    {
        $encodedPassword = $this->container->get('security.password_encoder')->encodePassword($user, $plainPassword);
        
    }
}
