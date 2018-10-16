<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class Customer extends Controller
{
    public function listCustomers(Request $request) 
    {
        return $this->render('@UVDeskCore/Customers/listSupportCustomers.html.twig');
    }

    public function createCustomer(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = new user();
        $errors = [];
        if($request->getMethod() == "POST") {
                $contentFile = $request->files->get('customer_form');

                $tempUser = $em->getRepository('UVDeskCoreBundle:User')->findBy(['email' =>  $request->request->get('customer_form')['email']]);
                if(!$tempUser) {

                    $content = $request->request->all();

                    $content = $content['customer_form'];
                    
                        $data = array(
                                    'firstName' => $content['firstName'],
                                    'lastName'  => $content['lastName'],
                                    'from'      => $content['email'],
                                    'fullname'  => $content['firstName'].' '.$content['lastName'],
                                    'contact'   => $content['contactNumber'],
                                    'active'    => isset($content['isActive']) && $content['isActive'] ? 1 : 0,
                                    'role'      => 4,
                                    'image'     => $contentFile['profileImage'],
                                    'source'    => 'website'
                                );

                        $user = $this->container->get('user.service')->getUserDetails($data);
                        $this->addFlash('success', 'Success ! Customer saved successfully.');

                        return $this->redirect($this->generateUrl('helpdesk_member_manage_customer_account_collection'));
                    // } else {
                    //     $errors = $this->getFormErrors($form);
                    // }
                } else {
                   
                    $this->addFlash('warning', 'Error ! User with same email is already exist.');
                }
        }

        return $this->render('@UVDeskCore/Customers/createSupportCustomer.html.twig', [
            'user' => $user,
            'errors' => json_encode($errors)
        ]);

    }

    public function editCustomer(Request $request)
    {
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('UVDeskCoreBundle:User');

      if($userId = $request->attributes->get('customerId')) {
          $user = $repository->findOneBy(['id' =>  $userId]);
          if(!$user)
              $this->noResultFound();
      } else
          $user = new user();
          
      $errors = [];
      if($request->getMethod() == "POST") {
        $contentFile = $request->files->get('customer_form');
          if($userId) {
              $data = $request->request->all();
              $data = $data['customer_form'];
              $checkUser = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $data['email']));
              $errorFlag = 0;
              if($checkUser) {
                  if($checkUser->getId() != $userId)
                      $errorFlag = 1;
              }

              if(!$errorFlag && 'hello@uvdesk.com' !== $user->getEmail()) {

                    $password = $user->getPassword();
                    $email = $user->getEmail();

                    $user->setFirstName($data['firstName']);
                    $user->setLastName($data['lastName']);
                    $user->setEmail($data['email']);
                    $user->setIsEnabled(isset($data['isActive']) ? 1 : 0);
                    $em->persist($user);

                    
                    $userInstance = $em->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(array('user' => $user->getId()));
                    $userInstance->setUser($user);
                    $userInstance->setIsActive(isset($data['isActive']) ? 1 : 0);
                    $userInstance->setIsVerified(0);
                    if(isset($data['source']))
                        $userInstance->setSource($data['source']);
                    else
                        $userInstance->setSource('website');
                    if(isset($data['contactNumber'])) {
                        $userInstance->setContactNumber($data['contactNumber']);
                    }
                    if(isset($contentFile['profileImage'])){
                        $fileName = $this->container->get('uvdesk.service')->getFileUploadManager()->upload($contentFile['profileImage']);
                        $userInstance->setProfileImagePath($fileName);
                    }
                        
                    $em->persist($userInstance);
                    $em->flush();
            
                    $user->addUserInstance($userInstance);
                    $em->persist($user);
                    $em->flush();

                    //Event Triggered
                    //   $this->get('event.manager')->trigger([
                    //           'event' => 'customer.updated',
                    //           'entity' => $user
                    // ]);

                      return $this->redirect($this->generateUrl('helpdesk_member_manage_customer_account_collection'));
                //   } else {
                //       $errors = $this->getFormErrors($form);
                //   }
              } else {
                  $this->addFlash(
                      'warning',
                      $this->translate('Error ! User with same email is already exist.')
                  );
                  return $this->redirect($this->generateUrl('edit_customer',array('id' => $userId)));
              }
          } 
        }
        
        return $this->render('@UVDeskCore/Customers/updateSupportCustomer.html.twig', [
                'user' => $user,
                'errors' => json_encode($errors)
        ]);
    }

    protected function encodePassword(User $user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
                   ->getEncoder($user);

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }

    public function bookmarkCustomer(Request $request)
    {
        $json = array();
        $em = $this->getDoctrine()->getManager();
        $data = json_decode($request->getContent(), true);
        $id = $request->attributes->get('id') ? : $data['id'];
        $user = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(['id' => $id]);
        if(!$user)  {
            $json['error'] = 'resource not found';
            return new JsonResponse($json, Response::HTTP_NOT_FOUND);
        }
        $userInstance = $em->getRepository('UVDeskCoreBundle:UserInstance')->findOneBy(array(
                'user' => $id,
                'supportRole' => 4
            )
        );

        if($userInstance->getIsStarred()) {
            $userInstance->setIsStarred(0);
            $em->persist($userInstance);
            $em->flush();
            $json['alertClass'] = 'success';
            $json['message'] = 'unstarred Action Completed successfully';             
        } else {
            $userInstance->setIsStarred(1);
            $em->persist($userInstance);
            $em->flush();
            $json['alertClass'] = 'success';
            $json['message'] = 'starred Action Completed successfully';             
        }
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}