<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Webkul\UVDesk\CoreBundle\Entity;
use Webkul\UVDesk\CoreBundle\Entity\UserInstance;

class Email extends Controller
{    
    const LIMIT = 10;
    protected function getTemplate($request)
    {
      
       $data = $this->getDoctrine()
                             ->getRepository('UVDeskCoreBundle:EmailTemplates')
                             ->findOneby([
                                    'id' => $request->attributes->get('template'),
                                    'user' => $this->container->get('user.service')->getCurrentUser()->getId()
                                ]);
        $default = $this->getDoctrine()
                             ->getRepository('UVDeskCoreBundle:EmailTemplates')
                             ->findOneby(['id' => $request->attributes->get('template')]);  
        return    $data == null ? $default : $data;
    }

    public function templates(Request $request) 
    {
        return $this->render('@UVDeskCore//templateList.html.twig');
    }

    public function emailTemplatesAction(Request $request) 
    {
        return $this->render('@UVDeskCore//emailTemplateList.html.twig', array(
            'list_items' => $this->getListItems($request),
            'information_items' => $this->getRightSidebarInfoItems($request),
        ));      
    }

    public function templateForm(Request $request) 
    {       
        if($request->attributes->get('template')) {
            $template = $this->getTemplate($request);
        } else {  
            $template = new Entity\EmailTemplates();
        }
        if(!$template)
            $this->noResultFound();

        if(!$template->getMessage())
            $template->setMessage('<p>{%global.companyLogo%}<hr></p><p><br><br><br></p><p><i>' . "Cheers !" . ' </i><br> <i style="color:#397b21">{%global.companyName%}</i><br></p>');
   

        $errors = [];      
        $this->get('session')->getFlashBag()->clear();

        if($request->getMethod() == 'POST') 
        {
           
                $entityManager= $this->getDoctrine()->getManager();
                $data = $request->request->all();

                $user_instance = $this->container->get('security.token_storage')->getToken()->getUser();
                $user_instance= $entityManager->getRepository(UserInstance::class)->findBy(['id'=>$user_instance->getId()]);
              
                $flag =0;

                $template->setUser($user_instance[0]);
                $template->setName($data['name']);
                $template->setSubject($data['subject']);
                $template->setMessage($data['message']);
                $template->setTemplateType($data['templateFor']);
                if(!$request->attributes->get('template'))
                $entityManager->persist($template);
                $entityManager->flush();

                if($request->attributes->get('template')) 
                    $message = 'Success! Template has been updated successfully.';
                else
                    $message = 'Success! Template has been added successfully.';

                $this->addFlash('success', $message);

                return $this->redirectToRoute('email_templates_action');
         
        } 

        return $this->render('@UVDeskCore//templateForm.html.twig', array(
            'template' => $template,
            'errors' => json_encode($errors)
        ));
    } 

    public function templatesxhr(Request $request) 
    {
        $json = array();
        $error = false;
        if($request->isXmlHttpRequest()) {
            if($request->getMethod() == 'GET') {
                $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:EmailTemplates');
                $json =  $repository->getTemplates($request->query, $this->container);
            }else{
                if($request->attributes->get('template'))
                {
                    if($templateBase = $this->getTemplate($request)) {
                        if($request->getMethod() == 'DELETE' ){
                            $em = $this->getDoctrine()->getManager();
                            $em->remove($templateBase);
                            $em->flush();

                            $json['alertClass'] = 'success';
                            $json['alertMessage'] = 'Success! Template has been deleted successfully.';
                        }else
                            $error = true;
                    } else{
                        $json['alertClass'] = 'danger';
                        $json['alertMessage'] = 'Warning! resource not found.';
                        $json['statusCode'] = Response::HTTP_NO_FOUND;                        
                    }
                }
            }
        }

        if($error) {
            $json['alertClass'] = 'danger';
            $json['alertMessage'] = 'Warning! You can not remove predefined email template which is being used in workflow(s).';
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function emailSetting(Request $request) 
    {
      
        return $this->render('@UVDeskCore//emailSetting.html.twig', array(
        ));
    }

}
