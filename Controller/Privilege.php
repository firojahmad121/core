<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Entity\SupportPrivilege;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Privilege extends Controller
{
    public function listPrivilege(Request $request) 
    {
        //$this->isAuthorized('ROLE_AGENT_MANAGE_AGENT_PRIVILEGE');
        return $this->render('@UVDeskCore/Privileges/listSupportPriveleges.html.twig');
    }

    public function createPrivilege(Request $request)
    {
        // $this->isAuthorized('ROLE_AGENT_MANAGE_AGENT_PRIVILEGE');
        $formErrors = [];
        $supportPrivilege = new SupportPrivilege();
        $supportPrivilegeResources = $this->get('support.service')->getSupportPrivelegesResources();
        if ('POST' == $request->getMethod()) {
            // $form = $this->createForm(new Privilege($this->container),$privilege, array('validation_groups' => array('AgentPrivilege', 'uniquePrivilege')));
            // $form->handleRequest($request);

            // $form = $this->createForm(Form\Privilege::class, $privilege, [
            //     'container' => $this->container,
            // ]);

            $entityManager = $this->getDoctrine()->getManager();
            $supportPrivelegeFormDetails = $request->request->get('privilege_form');
            $supportPrivilege->setName($supportPrivelegeFormDetails['name']);
            $supportPrivilege->setDescription($supportPrivelegeFormDetails['description']);
            $supportPrivilege->setPrivileges($supportPrivelegeFormDetails['privileges']);

            $entityManager->persist($supportPrivilege);
            $entityManager->flush();  

            $this->addFlash('success', 'Success ! Privilege information saved successfully.');
            return $this->redirect($this->generateUrl('helpdesk_member_privilege_collection'));

            // if ($form->isValid()) {
            //     $em = $this->getDoctrine()->getManager();
            //     //$privilege->setCompany($this->get('user.service')->getCurrentCompany());
            //     $em->persist($privilege);
            //     $em->flush();
            //     $this->addFlash(
            //         'success',
            //         $this->translate('Success ! Privilege information saved successfully.')
            //     );

            //     return $this->redirect($this->generateUrl('helpdesk_member_privilege_collection'));
            // } else {
            //     $formErrors = $this->getFormErrors($form);
            // }
        }

        return $this->render('@UVDeskCore/Privileges/createSupportPrivelege.html.twig', [
            'errors' => json_encode($formErrors),
            'supportPrivilege' => $supportPrivilege,
            'supportPrivilegeResources' => $supportPrivilegeResources,
        ]);
    }

    public function editPrivilege($supportPrivilegeId)
    {
        // $this->isAuthorized('ROLE_AGENT_MANAGE_AGENT_PRIVILEGE');
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->get('request_stack')->getCurrentRequest();
        
        $supportPrivilege = $entityManager->getRepository('UVDeskCoreBundle:SupportPrivilege')->findOneById($supportPrivilegeId);
        
        if (empty($supportPrivilege)) {
            $this->noResultFound();
        }
        
        $formErrors = [];
        $supportPrivilegeResources = $this->get('support.service')->getSupportPrivelegesResources();

        if ('POST' == $request->getMethod()) {
            //$form = $this->createForm(new Privilege($this->container),$privilege, array('validation_groups' => array('AgentPrivilege', 'uniquePrivilege')));
            // $form = $this->createForm(Form\Privilege::class, $privilege, [
            //         'container' => $this->container,
            // ]);

            $supportPrivilegeDetails = $request->request->get('privilege_form');

            $supportPrivilege->setName($supportPrivilegeDetails['name']);
            $supportPrivilege->setDescription($supportPrivilegeDetails['description']);
            $supportPrivilege->setPrivileges($supportPrivilegeDetails['privileges']);

            $entityManager->persist($supportPrivilege);
            $entityManager->flush();  

            $this->addFlash('success', 'Privilege updated successfully.');

            return $this->redirect($this->generateUrl('helpdesk_member_privilege_collection'));

            //  $form->handleRequest($request);
 
            //  if ($form->isValid()) {
            //      $em = $this->getDoctrine()->getManager();
            //      $em->persist($privilege);
            //      $em->flush();
            //      $this->addFlash(
            //          'success',
            //          $this->translate('Success ! Privilege information saved successfully.')
            //      );
 
            //      return $this->redirect($this->generateUrl('helpdesk_member_privilege_collection'));
            //  } else {
            //      $formErrors = $this->getFormErrors($form);
            //  }
        }
 
        return $this->render('@UVDeskCore/Privileges/updateSupportPrivelege.html.twig', [
            'errors' => json_encode($formErrors),
            'supportPrivilege' => $supportPrivilege,
            'supportPrivilegeResources' => $supportPrivilegeResources,
        ]);
    }
}