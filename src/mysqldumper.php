<?php

namespace mysqldumper\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class mysqldumper extends Command
{
    protected $cli;

    public function __construct($cli)
    {
        parent::__construct();
        $this->cli = $cli;
    }

    protected function configure()
    {
        $this->setName('dump')->setDescription('Dump the data')->addArgument(
            'env',
            InputArgument::REQUIRED,
            'Required option'
        )->addArgument(
            'destination',
            InputArgument::OPTIONAL,
            'optional argument?'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mysqldumper();
    }

    public function mysqldumper()
    {
        $this->out('Dumping...', 'success');
    }

    public function getRepoStatus()
    {
        return $this->git->status();
    }

    public function out($message, $style = 'info')
    {
        switch ($style) {
        case 'info':
            $this->cli->blue($message);
            break;
        case 'success':
            $this->cli->green($message);
            break;
        case 'error':
            $this->cli->red($message);
            break;
        }
    }
}
