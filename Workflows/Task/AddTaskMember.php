<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Task;

class AddTaskMember
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
