<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Ticket;

class AddCollaborator
{
    public static function getAlias()
    {
        return 'ticket.reply.added.agent';
    }

    public static function getAliasedGroup()
    {
        return 'ticket';
    }
}
