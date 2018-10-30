<?php
namespace Webkul\UVDesk\CoreBundle\Services;

use Doctrine\ORM\EntityManager;
use Webkul\UVDesk\CoreBundle\Entity\User;
use Webkul\UVDesk\CoreBundle\Utils\TokenGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\CoreBundle\Entity\EmailTemplates;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    private $request;
    private $container;
    private $entityManager;

    public function __construct(ContainerInterface $container, RequestStack $request, EntityManager $entityManager)
    {
        $this->request = $request;
        $this->container = $container;
        $this->entityManager = $entityManager;
    }

    public function trans($text)
    {
        return $this->container->get('translator')->trans($text);
    }

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

        return $placeHolders;
    }

    public function getEmailPlaceholderValues(User $user, $userType = 'member')
    {
        if (null == $user->getVerificationCode()) {
            // Set user verification code
            $user->setVerificationCode(TokenGenerator::generateToken());

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $router = $this->container->get('router');
        $helpdeskWebsite = $this->entityManager->getRepository('UVDeskCoreBundle:Website')->findOneByCode('helpdesk');

        // Link to update account login credentials
        $updateCredentialsURL = $router->generate(('customer' == $userType) ? 'helpdesk_customer_update_account_credentials' : 'helpdesk_member_update_account_credentials', [
            'email' => $user->getEmail(),
            'verificationCode' => $user->getVerificationCode(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $placeholderParams = [
            'user.userName' => $user->getFullName(),
            'user.userEmail' => $user->getEmail(),
            'user.assignUserEmail' => $user->getEmail(),
            'user.forgotPasswordLink' => "<a href='$updateCredentialsURL'>$updateCredentialsURL</a>",
            'user.accountValidationLink' => "<a href='$updateCredentialsURL'>$updateCredentialsURL</a>",
            'global.companyName' => $helpdeskWebsite->getName(),
            'global.companyLogo' => "<img style='max-height:60px' src='https://cdn.uvdesk.com/uvdesk/images/7c5ce25.png'/>",
            'global.companyUrl' => "<a href='https://community.uvdesk.com/en/in-action/'>https://community.uvdesk.com/en/in-action/</a>",
        ];

        return $placeholderParams;
    }

    public function processEmailSubject($subject, array $emailPlaceholders = [])
    {
        foreach ($emailPlaceholders as $var => $value) {
            $subject = str_replace("{%$var%}", $value, $subject);
        }
        
        return $subject;
    }

    public function processEmailContent($content, array $emailPlaceholders = [], $isSavedReply = false)
    {
        $twigTemplatingEngine = $this->container->get('twig');
        $baseEmailTemplate = $this->container->getParameter('uvdesk.default.templates.email');

        // if (!$isSavedReply && strpos($content, '<p>')) {
        //     $delimiter = $this->getEmailDelimiter();

        //     // $content = strstr($content,'<p>', true) . $delimiter . strstr($content,'<p>', false);
        //     // $content = str_replace('<p></p>', '', $content);
        // }

        foreach ($emailPlaceholders as $var => $value) {
            $content = str_replace("{%$var%}", $value, $content);
        }

        $content = $isSavedReply ? stripslashes($content) : htmlspecialchars_decode(preg_replace(['#&lt;script&gt;#', '#&lt;/script&gt;#'], ['&amp;lt;script&amp;gt;', '&amp;lt;/script&amp;gt;'], $content));

        return $twigTemplatingEngine->render($baseEmailTemplate, ['message' => $content]);
    }

    // protected function getEmailDelimiter()
    // {
    //     return '<p class="uv-delimiter-dXZkZXNr" style="font-size: 12px; color: #bdbdbd;"></p>';
    // }
}