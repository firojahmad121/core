<?php

namespace Webkul\UVDesk\CoreBundle\Console;

use Doctrine\ORM\EntityManager;
use Webkul\UVDesk\CoreBundle\Entity\Mailbox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RefreshMailboxCommand extends Command
{
    private $container;
    private $entityManager;

    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('uvdesk:refresh-mailbox');
        $this->setDescription('Check if any new emails have been received and process them into tickets');

        $this->addArgument('emails', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, "Email address of the mailboxes you wish to update");
        $this->addOption('timestamp', 't', InputOption::VALUE_REQUIRED, "Fetch messages no older than the given timestamp");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emails = array_map(function ($email) {
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        }, $input->getArgument('emails'));
        
        if (empty($emails)) {
            if (false === $input->getOption('no-interaction')) {
                $output->writeln("\n <comment>No mailbox emails specified.</comment>\n");
            }

            return;
        }

        $mailboxCollection = array_map(function ($mailboxID) {
            return $this->container->getParameter("uvdesk.mailboxes.$mailboxID");
        }, $this->container->getParameter('uvdesk.mailboxes'));

        $mailboxCollection = array_combine(array_column($mailboxCollection, 'email'), $mailboxCollection);
        $timestamp = new \DateTime(sprintf("-%u minutes", (int) ($input->getOption('timestamp') ?: 1440)));

        foreach ($emails as $mailboxEmail) {
            if (empty($mailboxCollection[$mailboxEmail])) {
                if (false == $input->getOption('no-interaction')) {
                    $output->writeln("\n <comment>Mailbox for email </comment><info>$mailboxEmail</info><comment> not found.</comment>\n");
                }

                continue;
            } else {
                $mailbox = $mailboxCollection[$mailboxEmail];

                if (false == $mailbox['enabled']) {
                    if (false === $input->getOption('no-interaction')) {
                        $output->writeln("\n <comment>Mailbox for email </comment><info>$mailboxEmail</info><comment> is not enabled.</comment>\n");
                    }
    
                    continue;
                } else if (empty($mailbox['imap_server'])) {
                    if (false === $input->getOption('no-interaction')) {
                        $output->writeln("\n <comment>No imap configurations defined for email </comment><info>$mailboxEmail</info><comment>.</comment>\n");
                    }
    
                    continue;
                }
            }

            $this->refreshMailbox($mailbox, $timestamp);
        }
    }

    public function refreshMailbox(array $mailbox, \DateTime $timestamp)
    {
        $imap = imap_open($mailbox->getHost(), $mailbox->getEmail(), $mailbox->getPassword());

        if ($imap) {
            $emailCollection = imap_search($imap, 'SINCE "' . $timestamp->format('d F Y') . '"');

            if (is_array($emailCollection)) {
                foreach ($emailCollection as $messageNumber) {
                    $message = imap_fetchbody($imap, $messageNumber, "");
                    $this->pushMessage($message);
                }
            }
        } else {
            dump(imap_last_error());
        }
        
        return;
    }

    public function pushMessage($message)
    {
        $router = $this->container->get('router');
        $router->getContext()->setHost($this->container->getParameter('uvdesk.site_url'));

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_POST, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $router->generate('helpdesk_member_mailbox_notification', [], UrlGeneratorInterface::ABSOLUTE_URL));
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query(['message' => $message]));
        $curlResponse = curl_exec($curlHandler);
        curl_close($curlHandler);
    }
}
