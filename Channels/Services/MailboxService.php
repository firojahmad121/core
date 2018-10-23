<?php

namespace Webkul\UVDesk\CoreBundle\Channels\Services;

use PhpMimeMailParser\Parser;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Utils\HTMLFilter;
use Webkul\UVDesk\CoreBundle\Utils\TokenGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MailboxService
{
    private $parser;
    private $container;
	private $requestStack;
    private $entityManager;

    public function __construct(ContainerInterface $container, RequestStack $requestStack, EntityManager $entityManager)
    {
        $this->parser = new Parser();
        $this->container = $container;
		$this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    private function getParser()
    {
        if (empty($this->parser)) {
            $this->parser = new Parser();
        }

        return $this->parser;
    }

    public function getMailbox($email)
    {
        $mailbox = $this->entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findOneByEmail($email);

        return !empty($mailbox) ? $mailbox : null;
    }

    public function getDefaultMailbox()
    {
        $defaultMailboxEmail = $this->container->getParameter('uvdesk.mailboxes');
        $mailbox = $this->entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findOneByEmail($defaultMailboxEmail);

        return !empty($mailbox) ? $mailbox : null;
    }

    public function getRandomizedMailboxEmail()
    {
        $mailboxEmail = TokenGenerator::generateToken(20, 'abcdefghijklmnopqrstuvwxyz0123456789') . $this->container->getParameter('uvdesk.email_domain');
        $mailbox = $this->entityManager->getRepository('UVDeskCoreBundle:Mailbox')->findOneByMailboxEmail($mailboxEmail);

        if (!empty($mailbox)) {
            $mailboxEmail = $this->getRandomMailboxId();
        }

        return $mailboxEmail;
    }

    public function parseAddress($type)
    {
        $addresses = mailparse_rfc822_parse_addresses($this->getParser()->getHeader($type));

        return $addresses ?: false;
    }

    public function getEmailAddress($addresses)
    {
        foreach ((array) $addresses as $address) {
            if (filter_var($address['address'], FILTER_VALIDATE_EMAIL)) {
                return $address['address'];
            }
        }

        return null;
    }

    private function searchExistingTickets(array $replyToAddresses = [], $replyTo = '', array $referenceIdCollection = [], $email, $subject = '')
    {
        $ticketRepository = $this->entityManager->getRepository('UVDeskCoreBundle:Ticket');
        $threadRepository = $this->entityManager->getRepository('UVDeskCoreBundle:Thread');

        // Search Criteria 1: Find ticket by unique reply to
        foreach ($replyToAddresses as $replyToAddress) {
            $ticket = $ticketRepository->findOneByUniqueReplyTo($replyToAddress);

            if (!empty($ticket)) {
                return $ticket;
            }
        }

        // Search Criteria 2: Find ticket based on in-reply-to reference id
        if (!empty($replyTo)) {
            $ticket = $ticketRepository->findOneByReferenceIds($replyTo);

            if (!empty($ticket)) {
                return $ticket;
            } else {
                $thread = $threadRepository->findOneByMessageId($replyTo);

                if (!empty($thread)) {
                    return $thread->getTicket();
                }
            }
        }

        // Search Criteria 3: Find ticket based on reference id
        foreach ($referenceIdCollection as $referenceId) {
            $ticket = $ticketRepository->findOneByReferenceIds($referenceId);

            if (!empty($ticket)) {
                return $ticket;
            }
        }


        // Search Criteria 4: Find ticket based on subject
        if (!empty($messageSubject)) {
            $ticket = $threadRepository->findTicketBySubject($senderEmail, $subject);

            if (!empty($ticket)) {
                return $ticket;
            }
        }
        
        return null;
    }

    public function sendMail($subject, $content, $recipient, array $headers = [])
    {
        $mailer = $this->container->get('swiftmailer.mailer.default');
        $supportEmail = $this->container->getParameter('uvdesk.support_email.id');
        $supportEmailName = $this->container->getParameter('uvdesk.support_email.name');

        // Set Message Id
        $headers['Message-ID'] = TokenGenerator::generateToken(20, 'abcdefghijklmnopqrstuvwxyz0123456789') . $this->container->getParameter('uvdesk.email_domain');

        // Create a message
        $message = (new \Swift_Message($subject))
            ->setFrom([$supportEmail => $supportEmailName])
            ->setTo($recipient)
            ->setBody($content, 'text/html');

        $swiftHeaders = $message->getHeaders();
        foreach ($headers as $headerName => $headerValue) {
            $swiftHeaders->addTextHeader($headerName, $headerName);
        }

        try {
            $messageId = $message->getId();
            $mailer->send($message);

            return "<$messageId>";
        } catch (\Exception $e) {
            dump($e);
        }

        return null;
    }
    
    public function processMail($rawEmail)
    {
        $mailData = [];
        $parser = $this->getParser();
        $parser->setText($rawEmail);

        $from = $this->parseAddress('from') ?: $this->parseAddress('sender');
        $addresses = [
            'from' => $this->getEmailAddress($from),
            'to' => $this->parseAddress('to'),
            'cc' => $this->parseAddress('cc'),
            'delivered-to' => $this->parseAddress('delivered-to'),
        ];

        if (empty($addresses['from'])) {
            return;
        } else {
            if (!empty($addresses['to'])) {
                $addresses['to'] = array_map(function($address) {
                    return $address['address'];
                }, $addresses['to']);
            } else if (!empty($addresses['cc'])) {
                $addresses['to'] = array_map(function($address) {
                    return $address['address'];
                }, $addresses['cc']);
            }
            
            // Skip email processing if no to-emails are specified
            if (empty($addresses['to'])) {
                return;
            }

            // Skip email processing if email is an auto-forwarded message to prevent infinite loop.
            if ($parser->getHeader('precedence') || $parser->getHeader('x-autoreply') || $parser->getHeader('x-autorespond') || 'auto-replied' == $parser->getHeader('auto-submitted')) {
                return;
            }

            // Check for self-referencing. Skip email processing if a mailbox is configured by the sender's address.
            if ($this->getMailbox($addresses['from'])) {
                return;
            }
        }

        // Process Mail - References
        $mailData['replyTo'] = $addresses['to'];
        $mailData['inReplyTo'] = htmlspecialchars_decode($parser->getHeader('in-reply-to'));
        $mailData['referenceIds'] = htmlspecialchars_decode($parser->getHeader('references'));
        $mailData['messageId'] = $parser->getHeader('message-id') ?: time() . '.' . uniqid() . $this->container->getParameter('uvdesk.email_domain');
        $mailData['cc'] = array_filter(explode(',', $parser->getHeader('cc'))) ?: [];
        $mailData['bcc'] = array_filter(explode(',', $parser->getHeader('bcc'))) ?: [];
        
        // Process Mail - User Details
        $mailData['source'] = 'email';
        $mailData['createdBy'] = 'customer';
        $mailData['role'] = 'ROLE_CUSTOMER';
        $mailData['from'] = $addresses['from'];
        $mailData['name'] = trim(current(explode('@', $from[0]['display'])));
        
        // Process Mail - Content
        $htmlFilter = new HTMLFilter();
        $mailData['subject'] = $parser->getHeader('subject');
        $mailData['message'] = $htmlFilter->HTMLFilter(autolink($htmlFilter->addClassEmailReplyQuote($parser->getMessageBody('text'))), '');

        // $mailboxes = $this->getMailboxByEmail($data['replyTo']);
        // if(!count($mailboxes)) {
        //     if($cc) {
        //         foreach ($cc as $value) {
        //             $toAdress[] = $value['address'];
        //         }
        //         $mailboxes = $this->getMailboxByEmail($toAdress);

        //         if(count($mailboxes)) {
        //             foreach ($mailboxes as $mailbox) {
        //                 foreach ($data['cc'] as $key => $value) {
        //                     if (strpos($value, $mailbox->getEmail()) !== FALSE) {
        //                         unset($data['cc'][$key]);
        //                     }
        //                 }
        //             }
        //             $data['replyTo'] = $toAdress;
        //         }
        //     }
        // }

        // $flag = (strpos($data['referenceIds'], 'mailbox') !== false) ? 1 : 0;

        // Search for any existing tickets
        $ticket = $this->searchExistingTickets($addresses['to'], $mailData['inReplyTo'], explode(' ', $mailData['referenceIds']) ?: [], $mailData['from'], $mailData['subject']);
        
        if (empty($ticket)) {
            $mailData['threadType'] = 'create';
            $mailData['referenceIds'] = $mailData['messageId'];

            $this->addCollaboratorFlag = 1;
            $thread = $this->container->get('ticket.service')->createTicket($mailData);
        } else if (false === $ticket->getIsTrashed() && strtolower($ticket->getStatus()->getCode()) != 'spam') {
            $thread = $this->entityManager->getRepository('UVDeskCoreBundle:Thread')->findOneByMessageId($mailData['messageId']);
            
            // if ($this->isEmailBlocked($data['from'],$ticket->getCompany()))
            //     return;

            // $mailData['ticket'] = $ticket;
            $mailData['threadType'] = 'reply';

            if ($ticket->getCustomer() && $ticket->getCustomer()->getEmail() == $mailData['from']) {
                // Reply from customer
                $user = $ticket->getCustomer();

                $mailData['user'] = $user;
                $userDetails = $user->getCustomerInstance()->getPartialDetails();
            } else {
                $user = $this->entityManager->getRepository('UVDeskSupportBundle:User')->findOneByEmail($mailData['from']);

                if (!empty($user) && null != $user->getAgentInstance()) {
                    $mailData['user'] = $user;
                    $userDetails = $user->getCustomerInstance()->getPartialDetails();
                } else {
                    // No user found.
                    dump('No user found');die;
                    return;
                }
            }

            $mailData['fullname'] = $userDetails['name'];
            $thread = $this->container->get('ticket.service')->createThread($ticket, $mailData);

            $updatedReferenceIds = $ticket->getReferenceIds() . ' ' . $mailData['messageId'];
            $ticket->setReferenceIds($mailData['referenceIds']);
            
            $this->entityManager->persist($ticket);
            $this->entityManager->flush();

            // $this->container->get('event.manager')->trigger([
            //         'event' => 'ticket.reply.added',
            //         'entity' => $thread->getTicket(),
            //         'targetEntity' => $thread,
            //         'user' => $thread->getUser(),
            //         'userType' => $thread->getUserType()
            //     ]);
        }

        return;
    }
}
