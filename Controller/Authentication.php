<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Component\Form\FormError;
use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class Authentication extends Controller
{
    public function login(Request $request)
    {
        if (null == $this->get('user.service')->getSessionUser()) {
            return $this->render('@UVDeskCore//login.html.twig', [
                'last_username' => $this->get('security.authentication_utils')->getLastUsername(),
                'error' => $this->get('security.authentication_utils')->getLastAuthenticationError(),
            ]);
        }
        
        return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
    }

    public function logout(Request $request)
    {
        return;
    }

    public function forgotPassword(Request $request)
    {
        if (null == $this->get('user.service')->getSessionUser()) {
            $entityManager = $this->getDoctrine()->getManager();
            $errors = [];
            
            if ($request->getMethod() == 'POST') {
                $user = new User();
                $form = $this->createFormBuilder($user,['csrf_protection' => false])
                        ->add('email',EmailType::class)
                        ->getForm();

                $form->submit(['email' => $request->request->get('forgot_password_form')['email']]);
                $form->handleRequest($request);
                
                if ($form->isValid()) {
                    $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:User');
                    $user = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $form->getData()->getEmail()));
                  
                    if($user) {
                        $request->getSession()->getFlashBag()->set(
                            'success','Please check your mail for password update.'
                        );
                        
                        return $this->redirect($this->generateUrl('helpdesk_member_update_account_credentials')."/".$form->getData()->getEmail());
                    } else {
                        $request->getSession()->getFlashBag()->set('warning', 'This Email address is not registered with us.');
                        
                        return $this->render("@UVDeskCore//forgotPassword.html.twig", [
                            'errors' => json_encode($errors)
                        ]);
                    }
                } else {
                    dump($form);die;
                    // $errors = /$this->getFormErrors($form);
                }
            }

            return $this->render("@UVDeskCore//forgotPassword.html.twig", [
                'errors' => json_encode($errors)
            ]);
        }
        
        return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));       
    }

    public function updateCredentials($email, Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
    
        $errors = [];
        $error = $form = false;

        if ($request->getMethod() == 'POST') {
            $user = $entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $email));
            $data = $request->request->all();
            if ($data['password']['first']===$data['password']['second']) {
                $user->setPassword($this->encodePassword($user, $data['password']['first']));
                $entityManager->persist($user);
                $entityManager->flush();            
                $request->getSession()->getFlashBag()->set('success', 'Your password changed.');
                
                return  $this->redirect($this->generateUrl('helpdesk_member_handle_login'));
            } else {
                $request->getSession()->getFlashBag()->set('warning', 'Password does not match.');
                
                return $this->render("@UVDeskCore//resetPassword.html.twig", [
                    'errors' => json_encode($errors)
                ]);
            }
        }
       

        return $this->render("@UVDeskCore//resetPassword.html.twig", [
            'errors' => json_encode($errors)
        ]);
    }

    protected function encodePassword(User $user, $plainPassword)
    {
      return  $encodedPassword = $this->container->get('security.password_encoder')->encodePassword($user, $plainPassword);
    }
}
