<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Ticket;

class AddNote
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
