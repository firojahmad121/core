<?php
namespace Webkul\UVDesk\CoreBundle\Controller\Dashboard;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GroupXHR extends Controller
{
    public function listGroupsXHR(Request $request)
    {
        if (true === $request->isXmlHttpRequest()) {
            $paginationResponse = $this->getDoctrine()->getRepository('UVDeskCoreBundle:SupportGroup')->getAllGroups($request->query, $this->container);

            return new Response(json_encode($paginationResponse), 200, ['Content-Type' => 'application/json']);
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }

    public function deleteGroupXHR($supportGroupId)
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        if ($request->getMethod() == "DELETE") {
            $entityManager = $this->getDoctrine()->getManager();
            $supportGroup = $entityManager->getRepository('UVDeskCoreBundle:SupportGroup')->findOneById($supportGroupId);

            if (!empty($supportGroup)) {
                $entityManager->remove($supportGroup);
                $entityManager->flush();
                
                return new Response(json_encode([
                    'alertClass' => 'success',
                    'alertMessage' => 'Support Group removed successfully.',
                ]), 200, ['Content-Type' => 'application/json']);
            }
        }
        
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }
}
