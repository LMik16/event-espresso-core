<?php

namespace EventEspresso\core\services\commands;

if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



/**
 * Interface CommandHandlerManagerInterface
 *
 * @package EventEspresso\core\services\commands
 */
interface CommandHandlerManagerInterface
{

    /**
     * @param CommandHandlerInterface $command_handler
     * @return mixed
     */
    public function addCommandHandler(CommandHandlerInterface $command_handler);



    /**
     * @param CommandInterface $command
     * @param CommandBus       $command_bus
     * @return mixed
     */
    public function getCommandHandler(CommandInterface $command, CommandBus $command_bus = null);

}
// End of file CommandHandlerManagerInterface.php
// Location: core/services/commands/CommandHandlerManagerInterface.php