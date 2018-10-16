<?php

namespace Webkul\UVDesk\CoreBundle\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Webkul\UVDesk\CoreBundle\Entity as CoreEntities;
use Doctrine\Bundle\FixturesBundle\Fixture as DoctrineFixture;
use Webkul\UVDesk\CoreBundle\Templates\Email\Resources as CoreEmailTemplates;

class EmailTemplates extends DoctrineFixture
{
    private static $seeds = [
        CoreEmailTemplates\Task\TaskCreated::class,
        CoreEmailTemplates\Task\TaskMemberAdded::class,
        CoreEmailTemplates\Task\TaskAgentReplied::class,
        CoreEmailTemplates\Account\AccountActivation::class,
        CoreEmailTemplates\Account\AgentForgotPassword::class,
        CoreEmailTemplates\Account\CustomerForgotPassword::class,
        CoreEmailTemplates\Ticket\MailCollaborator::class,
        CoreEmailTemplates\Ticket\TicketCollaboratorAdded::class,
        CoreEmailTemplates\Ticket\TicketCollaboratorReplied::class,
        CoreEmailTemplates\Ticket\CustomerTicketCreated::class,
        CoreEmailTemplates\Ticket\TicketCustomerReplied::class,
        CoreEmailTemplates\Ticket\TicketCreatedByAgent::class,
        CoreEmailTemplates\Ticket\TicketCreatedByCustomer::class,
        CoreEmailTemplates\Ticket\TicketAssignedToAgent::class,
        CoreEmailTemplates\Ticket\TicketAgentReplied::class,
        CoreEmailTemplates\Ticket\TicketAwaitingResponse::class,
    ];

    public function load(ObjectManager $entityManager)
    {
        $emailTemplateCollection = $entityManager->getRepository('UVDeskCoreBundle:EmailTemplates')->findAll();

        if (empty($emailTemplateCollection)) {
            foreach (self::$seeds as $coreEmailTemplate) {
                ($emailTemplate = new CoreEntities\EmailTemplates())
                    ->setName($coreEmailTemplate::getName())
                    ->setSubject($coreEmailTemplate::getSubject())
                    ->setMessage($coreEmailTemplate::getMessage());

                $entityManager->persist($emailTemplate);
            }

            $entityManager->flush();
        }

        die;
    }
}
