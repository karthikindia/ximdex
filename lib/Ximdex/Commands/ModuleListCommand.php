<?php

namespace Ximdex\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ximdex\Modules\Manager;

class ModuleListCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:list')
            ->setDescription('List Modules')
            ->addOption(
                'show-path',
                null,
                InputOption::VALUE_NONE,
                'Show Module full path'
            )

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {



        foreach(Manager::getModules() as $module){

            $installed = ($module['enable'] ) ? '<info>Installed</info>': '<error>Not installed</error>' ;
            
            $output->writeln( $module['name'] . " "  . $installed) ;

            if ($input->getOption('show-path')) {
                $output->writeln( "\tPath: <fg=black;bg=cyan>" . $module['path'] . "</>" ) ;

            }
            $output->writeln( "" ) ;


        }
    }
}