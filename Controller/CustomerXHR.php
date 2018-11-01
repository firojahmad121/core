<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CustomerXHR extends Controller
{
    public function listCustomersXHR(Request $request) 
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')) {          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }
        
        $json = array();
        
        if($request->isXmlHttpRequest()) {
            $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:User');
            $json =  $repository->getAllCustomer($request->query, $this->container);
        }
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }

    public function updateCustomerXHR(Request $request) 
    {
        if (!$this->get('user.service')->checkPermission('ROLE_AGENT_MANAGE_CUSTOMER')) {          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }
        
        $json = array();
        $entityManager = $this->getDoctrine()->getManager();
        switch ($request->getMethod())
         {
            case 'DELETE':
                $id = $request->attributes->get('customerId');
                $user = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy(['id' => $id]);

                if($user) {

                    $this->get('user.service')->removeCustomer($user);
                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = ('Success ! Customer removed successfully.');
                } else {
                    $json['alertClass'] =  'danger';
                    $json['alertMessage'] = ('Error ! Invalid customer id.');
                    $json['statusCode'] = Response::HTTP_NOT_FOUND;
                }
                break;
            case "PUT":
                    $id = $request->attributes->get('customerId');
                    $data = json_decode($request->getContent(),true);
                    $errorFlag=0;
                    
                    $checkUser = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy([ 'email' => $data['email'] ]);
                    if($checkUser) {
                        if($checkUser->getId() != $data['id'])
                        $errorFlag = 1;
                    }
                    
                    if(!$errorFlag)
                    {
                        $userInstance = $entityManager->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(array('user' => $checkUser->getId()));
                        $name = explode(" ",$data['name']);

                        $checkUser->setFirstName($name[0]);
                       
                        unset($name[0]);
                        $checkUser->setLastName(implode(' ',$name));
                        $checkUser->setEmail($data['email']);
                        
                        if($data['contactNumber'] != "" ) {
                            $userInstance->setContactNumber($data['contactNumber']);
                        }
                        $entityManager->persist($userInstance);
                        $entityManager->persist($checkUser);
                        $entityManager->flush();
                        
                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = ('Success ! Customer update successfully.');
                    } else {
                        $json['alertClass'] =  'danger';
                        $json['alertMessage'] = ('Error ! Invalid customer id.');
                        $json['statusCode'] = Response::HTTP_NOT_FOUND;
                    }
            break;
            default:
               
                break;
        }
      
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
   
}
