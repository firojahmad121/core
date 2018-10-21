<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Task;

class AddTaskReply
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
