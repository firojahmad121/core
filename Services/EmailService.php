<?php
namespace Webkul\UVDesk\CoreBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\CoreBundle\Entity\EmailTemplates;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailService {

    private $request;
    private $container;
    private $em;
    public $emailTrackingId;

    public function __construct(ContainerInterface $container, RequestStack $request, EntityManager $em)
    {
        $this->request = $request;
        $this->container = $container;
        $this->em = $em;
    }

    public function trans($text)
    {
        return $this->container->get('translator')->trans($text);
    }

    public function getEmailTemplate($mailFor)
    {
        if(!$mailFor) return null;
        $template = array();
        switch($mailFor){
            case EmailTemplates::AccountValidation:
            case EmailTemplates::AccountActivation:
            case EmailTemplates::ForgotPassword:
            case EmailTemplates::AgentForgotPassword:
            case EmailTemplates::CustomerForgotPassword:
            case EmailTemplates::TicketGeneratedByCustomer:
            case EmailTemplates::TicketGeneratedByAgent:
            case EmailTemplates::TicketAssign:
            case EmailTemplates::AgentReplyToTheCustomersTicket:
            case EmailTemplates::CustomerReplyToTheAgent:
            case EmailTemplates::MailToTheCollaborator:
            case EmailTemplates::CollaboratorAddedToTicket:
            case EmailTemplates::CollaboratorAddedReply:
            case EmailTemplates::TicketGenerateSuccessMailForCustomer:
            case EmailTemplates::TaskCreated:
            case EmailTemplates::MemberAddedInTask:
            case EmailTemplates::MemberReplyInTask:
            case EmailTemplates::CompanyCreatedByCustomer:
            case EmailTemplates::CompanyCreateUs:
                $queryBuilder = $this->em
                                     ->getRepository('WebkulUserBundle:EmailTemplatesCompany')
                                     ->createQueryBuilder('etc');
                $template = $queryBuilder->select('etc')
                         ->leftJoin('Webkul\AdminBundle\Entity\EmailTemplates','et','WITH', 'etc.templateId = et.id')
                         ->where('et.selector = :selector')
                         ->andWhere('etc.companyId = :companyId')
                         ->setParameters(
                            array(
                                    'selector' => $mailFor,
                                    'companyId' => $companyId,
                                )
                            )
                          ->getQuery()
                          ->getOneOrNullResult()
                        ;
                if(!$template){
                    $queryBuilder = $this->em
                                         ->getRepository('WebkulAdminBundle:EmailTemplates')
                                         ->createQueryBuilder('et');

                    $template = $queryBuilder->select('et')
                         ->where('et.selector = :selector')
                         ->setParameters(
                            array(
                                    'selector' => $mailFor,
                                )
                            )
                          ->getQuery()
                          ->getOneOrNullResult()
                        ;
                }

                break;
            default:
                $repository = $this->em->getRepository('WebkulUserBundle:EmailTemplatesCompany');
                $template = $repository->findOneBy(
                                array('id' => $mailFor)
                            );
                $currentPlan = $this->container->get('user.service')->getCurrentPlan();
                if(($currentPlan && $currentPlan->getWorkflow() == 'predefind') && $template && !$template->getIsPredefind())
                    $template = null;
                break;
        }

        return $template;
    }

    /**
    * get email placeholders for different pages
    * possible $params values:
    * savedReply: for saved replies
    * template: for email templates
    * taskNote, ['match'] = 'task': for taskNote placeholders
    * ticketNote, ['match'] = 'ticket': for ticketNote placeholders
    * manualNote, ['match'] = manual: for manualNote placeholders
    *
    * @param String/Array $params
    *
    * @return Array $placeholders
    */
    public function getEmailPlaceHolders($params)
    {
        if(is_array($params))
            $default = $params['match'].'Note';
        elseif($params)
            $default = $params;
        else
            $default = 'template';

        $placeHolders = [];

        $allEmailPlaceholders = [
            'global' => [
                        'companyName' => [
                                    'title' => $this->trans('Company Name'),
                                    'info' => $this->trans('global.company.name.info'),
                                ],
                        'companyUrl' => [
                                    'title' => $this->trans('Company Url'),
                                    'info' => $this->trans('global.company.url.info'),
                                ],
                        'companyLogo' => [
                                    'title' => $this->trans('Company Logo'),
                                    'info' => $this->trans('global.company.logo.info'),
                                ],
                    ],

        ];

        if($default == 'template') {
            $placeHolders = [
                                'ticket' => [
                                                'id' => [
                                                            'title' => $this->trans('Ticket Id'),
                                                            'info' => $this->trans('ticket.id.placeHolders.info'),
                                                        ],
                                                'subject' => [
                                                            'title' => $this->trans('Ticket Subject'),
                                                            'info' => $this->trans('ticket.subject.placeHolders.info'),
                                                        ],
                                                'message' => [
                                                            'title' => $this->trans('Ticket Message'),
                                                            'info' => $this->trans('ticket.message.placeHolders.info'),
                                                        ],
                                                'attachments' => [
                                                            'title' => $this->trans('Ticket Attachments'),
                                                            'info' => $this->trans('ticket.attachments.placeHolders.info'),
                                                        ],
                                                'threadMessage' => [
                                                            'title' => $this->trans('Ticket Thread Message'),
                                                            'info' => $this->trans('ticket.threadMessage.placeHolders.info'),
                                                        ],
                                                'tags' => [
                                                            'title' => $this->trans('Ticket Tags'),
                                                            'info' => $this->trans('ticket.tags.placeHolders.info'),
                                                        ],
                                                'source' => [
                                                            'title' => $this->trans('Ticket Source'),
                                                            'info' => $this->trans('ticket.source.placeHolders.info'),
                                                        ],
                                                'status' => [
                                                            'title' => $this->trans('Ticket Status'),
                                                            'info' => $this->trans('ticket.status.placeHolders.info'),
                                                        ],
                                                'priority' => [
                                                            'title' => $this->trans('Ticket Priority'),
                                                            'info' => $this->trans('ticket.priority.placeHolders.info'),
                                                        ],
                                                'group' => [
                                                            'title' => $this->trans('Ticket Group'),
                                                            'info' => $this->trans('ticket.group.placeHolders.info'),
                                                        ],
                                                'team' => [
                                                            'title' => $this->trans('Ticket Team'),
                                                            'info' => $this->trans('ticket.team.placeHolders.info'),
                                                        ],
                                                'customerName' => [
                                                            'title' => $this->trans('Ticket Customer Name'),
                                                            'info' => $this->trans('ticket.customerName.placeHolders.info'),
                                                        ],
                                                'customerEmail' => [
                                                            'title' => $this->trans('Ticket Customer Email'),
                                                            'info' => $this->trans('ticket.customerEmail.placeHolders.info'),
                                                        ],
                                                'agentName' => [
                                                            'title' => $this->trans('Ticket Agent Name'),
                                                            'info' => $this->trans('ticket.agentName.placeHolders.info'),
                                                        ],
                                                'agentEmail' => [
                                                            'title' => $this->trans('Ticket Agent Email'),
                                                            'info' => $this->trans('ticket.agentEmail.placeHolders.info'),
                                                        ],
                                                'link' => [
                                                            'title' => $this->trans('Ticket Link'),
                                                            'info' => $this->trans('ticket.link.placeHolders.info'),
                                                        ],
                                                'collaboratorName' => [
                                                            'title' => $this->trans('Last Collaborator Name'),
                                                            'info' => $this->trans('ticket.collaborator.name.placeHolders.info'),
                                                        ],
                                                'collaboratorEmail' => [
                                                            'title' => $this->trans('Last Collaborator Email'),
                                                            'info' => $this->trans('ticket.collaborator.email.placeHolders.info'),
                                                        ],
                                            ],
                                'task' => [
                                                'id' => [
                                                            'title' => $this->trans('Task Id'),
                                                            'info' => $this->trans('task.id.placeHolders.info'),
                                                        ],
                                                'subject' => [
                                                            'title' => $this->trans('Task Subject'),
                                                            'info' => $this->trans('task.subject.placeHolders.info'),
                                                        ],
                                                'message' => [
                                                            'title' => $this->trans('Task Message'),
                                                            'info' => $this->trans('task.message.placeHolders.info'),
                                                        ],
                                                'attachments' => [
                                                            'title' => $this->trans('Task Attachments'),
                                                            'info' => $this->trans('task.attachments.placeHolders.info'),
                                                        ],
                                                'threadMessage' => [
                                                            'title' => $this->trans('Task Thread Message'),
                                                            'info' => $this->trans('task.threadMessage.placeHolders.info'),
                                                        ],
                                                'stage' => [
                                                            'title' => $this->trans('Task Stages'),
                                                            'info' => $this->trans('task.stages.placeHolders.info'),
                                                        ],
                                                'priority' => [
                                                            'title' => $this->trans('Task Priority'),
                                                            'info' => $this->trans('task.priority.placeHolders.info'),
                                                        ],
                                                'assignedAgentName' => [
                                                            'title' => $this->trans('Task Assigned Agent Name'),
                                                            'info' => $this->trans('task.assignedAgentName.placeHolders.info'),
                                                        ],
                                                'assignedAgentEmail' => [
                                                            'title' => $this->trans('Task Assigned Agent Email'),
                                                            'info' => $this->trans('task.assignedAgentEmail.placeHolders.info'),
                                                        ],
                                                'assignUserName' => [
                                                            'title' => $this->trans('Task Assigner Agent Name'),
                                                            'info' => $this->trans('task.assignUserName.placeHolders.info'),
                                                        ],
                                                'assignUserEmail' => [
                                                            'title' => $this->trans('Task Assigner Agent Email'),
                                                            'info' => $this->trans('task.assignUserEmail.placeHolders.info'),
                                                        ],
                                                'link' => [
                                                            'title' => $this->trans('Task Link'),
                                                            'info' => $this->trans('task.link.placeHolders.info'),
                                                        ],
                                                'memberName' => [
                                                            'title' => $this->trans('Last Member Name'),
                                                            'info' => $this->trans('ticket.member.name.placeHolders.info'),
                                                        ],
                                                'memberEmail' => [
                                                            'title' => $this->trans('Last Member Email'),
                                                            'info' => $this->trans('ticket.member.email.placeHolders.info'),
                                                        ]
                                            ],
                                'user'  => [
                                                'userName' => [
                                                            'title' => $this->trans('Agent/ Customer Name'),
                                                            'info' => $this->trans('user.name.info'),
                                                        ],
                                                'userEmail' => [
                                                            'title' => $this->trans('Email'),
                                                            'info' => $this->trans('user.email.info'),
                                                        ],
                                                'accountValidationLink' => [
                                                            'title' => $this->trans('Account Validation Link'),
                                                            'info' => $this->trans('user.account.validate.link.info'),
                                                        ],
                                                'forgotPasswordLink' => [
                                                            'title' => $this->trans('Password Forgot Link'),
                                                            'info' => $this->trans('user.password.forgot.link.info'),
                                                        ],
                                            ],
                                'global' => $allEmailPlaceholders['global'],
                            ];
        } elseif($default == 'savedReply') {
            $placeHolders = [
                                'ticket' => [
                                                'id' => [
                                                            'title' => $this->trans('Ticket Id'),
                                                            'info' => $this->trans('ticket.id.placeHolders.info'),
                                                        ],
                                                'subject' => [
                                                            'title' => $this->trans('Ticket Subject'),
                                                            'info' => $this->trans('ticket.subject.placeHolders.info'),
                                                        ],
                                                'status' => [
                                                            'title' => $this->trans('Ticket Status'),
                                                            'info' => $this->trans('ticket.status.placeHolders.info'),
                                                        ],
                                                'priority' => [
                                                            'title' => $this->trans('Ticket Priority'),
                                                            'info' => $this->trans('ticket.priority.placeHolders.info'),
                                                        ],
                                                'group' => [
                                                            'title' => $this->trans('Ticket Group'),
                                                            'info' => $this->trans('ticket.group.placeHolders.info'),
                                                        ],
                                                'team' => [
                                                            'title' => $this->trans('Ticket Team'),
                                                            'info' => $this->trans('ticket.team.placeHolders.info'),
                                                        ],
                                                'customerName' => [
                                                            'title' => $this->trans('Ticket Customer Name'),
                                                            'info' => $this->trans('ticket.customerName.placeHolders.info'),
                                                        ],
                                                'customerEmail' => [
                                                            'title' => $this->trans('Ticket Customer Email'),
                                                            'info' => $this->trans('ticket.customerEmail.placeHolders.info'),
                                                        ],
                                                'agentName' => [
                                                            'title' => $this->trans('Ticket Agent Name'),
                                                            'info' => $this->trans('ticket.agentName.placeHolders.info'),
                                                        ],
                                                'agentEmail' => [
                                                            'title' => $this->trans('Ticket Agent Email'),
                                                            'info' => $this->trans('ticket.agentEmail.placeHolders.info'),
                                                        ],
                                                'link' => [
                                                            'title' => $this->trans('Ticket Link'),
                                                            'info' => $this->trans('ticket.link.placeHolders.info'),
                                                        ],
                                            ],
                                'global' => $allEmailPlaceholders['global'],
                            ];
        } elseif($default == 'taskNote') {
            $placeHolders = [
                                $this->trans('ticket') => [
                                                'id' => [
                                                            'title' => $this->trans('Ticket Id'),
                                                            'info' => $this->trans('ticket.id.placeHolders.info'),
                                                        ],
                                                'subject' => [
                                                            'title' => $this->trans('Ticket Subject'),
                                                            'info' => $this->trans('ticket.subject.placeHolders.info'),
                                                        ],
                                                'status' => [
                                                            'title' => $this->trans('Ticket Status'),
                                                            'info' => $this->trans('ticket.status.placeHolders.info'),
                                                        ],
                                                'priority' => [
                                                            'title' => $this->trans('Ticket Priority'),
                                                            'info' => $this->trans('ticket.priority.placeHolders.info'),
                                                        ],
                                                'group' => [
                                                            'title' => $this->trans('Ticket Group'),
                                                            'info' => $this->trans('ticket.group.placeHolders.info'),
                                                        ],
                                                'customerName' => [
                                                            'title' => $this->trans('Ticket Customer Name'),
                                                            'info' => $this->trans('ticket.customerName.placeHolders.info'),
                                                        ],
                                                'customerEmail' => [
                                                            'title' => $this->trans('Ticket Customer Email'),
                                                            'info' => $this->trans('ticket.customerEmail.placeHolders.info'),
                                                        ],
                                                'agentName' => [
                                                            'title' => $this->trans('Ticket Agent Name'),
                                                            'info' => $this->trans('ticket.agentName.placeHolders.info'),
                                                        ],
                                                'agentEmail' => [
                                                            'title' => $this->trans('Ticket Agent Email'),
                                                            'info' => $this->trans('ticket.agentEmail.placeHolders.info'),
                                                        ],
                                            ],
                                $this->trans('global') => $allEmailPlaceholders['global'],
                            ];
        } elseif($default == 'ticketNote') {
            $placeHolders = [
                                $this->trans('type') => [
                                                'previousType' => [
                                                            'title' => $this->trans('Previous Type'),
                                                            'info' => $this->trans('type.previous.placeHolders.info'),
                                                        ],
                                                'updatedType' => [
                                                            'title' => $this->trans('Updated Type'),
                                                            'info' => $this->trans('type.updated.placeHolders.info'),
                                                        ],
                                            ],
                                $this->trans('status') => [
                                                'previousStatus' => [
                                                            'title' => $this->trans('Previous Status'),
                                                            'info' => $this->trans('status.previous.placeHolders.info'),
                                                        ],
                                                'updatedStatus' => [
                                                            'title' => $this->trans('Updated Status'),
                                                            'info' => $this->trans('status.updated.placeHolders.info'),
                                                        ],
                                            ],
                                $this->trans('group') => [
                                                'previousGroup' => [
                                                            'title' => $this->trans('Previous Group'),
                                                            'info' => $this->trans('group.previous.placeHolders.info'),
                                                        ],
                                                'updatedGroup' => [
                                                            'title' => $this->trans('Updated Group'),
                                                            'info' => $this->trans('group.updated.placeHolders.info'),
                                                        ],
                                            ],
                                $this->trans('team') => [
                                                'previousTeam' => [
                                                            'title' => $this->trans('Previous Team'),
                                                            'info' => $this->trans('team.previous.placeHolders.info'),
                                                        ],
                                                'updatedTeam' => [
                                                            'title' => $this->trans('Updated Team'),
                                                            'info' => $this->trans('team.updated.placeHolders.info'),
                                                        ],
                                            ],
                                $this->trans('priority') => [
                                                'previousPriority' => [
                                                            'title' => $this->trans('Previous Priority'),
                                                            'info' => $this->trans('priority.previous.placeHolders.info'),
                                                        ],
                                                'updatedPriority' => [
                                                            'title' => $this->trans('Updated Priority'),
                                                            'info' => $this->trans('priority.updated.placeHolders.info'),
                                                        ],
                                            ],
                                $this->trans('agent') => [
                                                'previousAgent' => [
                                                            'title' => $this->trans('Previous Agent'),
                                                            'info' => $this->trans('agent.previous.placeHolders.info'),
                                                        ],
                                                'updatedAgent' => [
                                                            'title' => $this->trans('Updated Agent'),
                                                            'info' => $this->trans('agent.updated.placeHolders.info'),
                                                        ],
                                                'responsePerformingAgent' => [
                                                            'title' => $this->trans('Response Performing Agent'),
                                                            'info' => $this->trans('agent.response.placeHolders.info'),
                                                        ],
                                            ],
                            ];

        } elseif($default == 'manualNote') {
            $placeHolders = [
                                $this->trans('ticket') => [
                                                'id' => [
                                                            'title' => $this->trans('Ticket Id'),
                                                            'info' => $this->trans('ticket.id.placeHolders.info'),
                                                        ],
                                                'subject' => [
                                                            'title' => $this->trans('Ticket Subject'),
                                                            'info' => $this->trans('ticket.subject.placeHolders.info'),
                                                        ],
                                                'status' => [
                                                            'title' => $this->trans('Ticket Status'),
                                                            'info' => $this->trans('ticket.status.placeHolders.info'),
                                                        ],
                                                'priority' => [
                                                            'title' => $this->trans('Ticket Priority'),
                                                            'info' => $this->trans('ticket.priority.placeHolders.info'),
                                                        ],
                                                'group' => [
                                                            'title' => $this->trans('Ticket Group'),
                                                            'info' => $this->trans('ticket.group.placeHolders.info'),
                                                        ],
                                                'team' => [
                                                            'title' => $this->trans('Ticket Team'),
                                                            'info' => $this->trans('ticket.team.placeHolders.info'),
                                                        ],
                                                'customerName' => [
                                                            'title' => $this->trans('Ticket Customer Name'),
                                                            'info' => $this->trans('ticket.customerName.placeHolders.info'),
                                                        ],
                                                'customerEmail' => [
                                                            'title' => $this->trans('Ticket Customer Email'),
                                                            'info' => $this->trans('ticket.customerEmail.placeHolders.info'),
                                                        ],
                                                'agentName' => [
                                                            'title' => $this->trans('Ticket Agent Name'),
                                                            'info' => $this->trans('ticket.agentName.placeHolders.info'),
                                                        ],
                                                'agentEmail' => [
                                                            'title' => $this->trans('Ticket Agent Email'),
                                                            'info' => $this->trans('ticket.agentEmail.placeHolders.info'),
                                                        ],
                                            ],
                                $this->trans('global') => $allEmailPlaceholders['global'],
                            ];

        }
    //    dump($placeHolders);die;

        return $placeHolders;
    }

    public function getProcessedTemplate($body,$emailTempVariables, $isSavedReply = false)
    {
        if(!$isSavedReply && strpos($body, '<p>')) {
            $delimiter = $this->getEmailDelimiter();
            if($this->emailTrackingId && $company = $this->container->get('user.service')->getCurrentCompany())
                $body = strstr($body,'<p>', true).'<img style="display: none" src="'.$this->container->get('default.service')->getUrl(['companyId' => $company->getId(), 'route' => 'thread_opent_tracking', 'params' => ['id' => $this->emailTrackingId]]).'"/>'.strstr($body,'<p>', false);
            $body = strstr($body,'<p>', true).$delimiter.strstr($body,'<p>', false);
            $body = str_replace('<p></p>', '', $body);

            if ($this->container->get('user.service')->getCurrentCompany()) {
                if ($this->container->get('user.service')->checkCompanyPermission('remove_branding_content')) {
                    $body = str_replace('Delivered by <a href="https://uvdesk.com">UVdesk</a>.', '', $body);
                    $body = str_replace('Delivered by <a href="https://uvdesk.com" style="background-color:transparent">UVdesk</a>.', '', $body);
                }

                if ($this->container->get('user.service')->checkCompanyPermission('remove_email_service_content')) {
                    $body = str_replace('This email is a service from ' . $this->container->get('user.service')->getCurrentCompany()->getName() . '.', '', $body);
                }
            }
        }
        foreach ($emailTempVariables as $var => $value) {
            $placeholder = "{%".$var."%}";
            $body = str_replace($placeholder,$value,$body);
        }
        $result = stripslashes($body);
        return $isSavedReply ? $result : htmlspecialchars_decode(preg_replace(['#&lt;script&gt;#', '#&lt;/script&gt;#'], ['&amp;lt;script&amp;gt;', '&amp;lt;/script&amp;gt;'] , $result));
    }

    public function getProcessedSubject($subject,$emailTempVariables) {
        foreach ($emailTempVariables as $var => $value) {
            $placeholder = "{%".$var."%}";
            $subject = str_replace($placeholder,$value,$subject);
        }
        return $subject;
    }

    protected function getEmailDelimiter()
    {
        $delimiter = $this->container->get('user.service')->getCurrentCompany() ? $this->container->get('user.service')->getCurrentCompany()->getEmailDelimiter() : '';
        return '<p class="uv-delimiter-dXZkZXNr" style="font-size: 12px; color: #bdbdbd;">'.htmlentities($delimiter).'</p>';
    }
}