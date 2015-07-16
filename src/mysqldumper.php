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
        $this->setName('dump')->setDescription('Dump the data');
        // ->addArgument(
        //     'env',
        //     InputArgument::REQUIRED,
        //     'Required option'
        // )->addArgument(
        //     'destination',
        //     InputArgument::OPTIONAL,
        //     'optional argument?'
        // );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mysqldumper();
    }

    public function mysqldumper()
    {
        $config = json_decode(file_get_contents('config.json'));
        try {
            $conn = new \PDO("mysql:host=".$config->host.";dbname=".$config->db, $config->user, $config->pass);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $conn->prepare('SHOW TABLES'); 
            $stmt->setFetchMode(\PDO::FETCH_ASSOC); // set the resulting array to associative
            $stmt->execute();
            
            $table_list  = $stmt->fetchAll();
            $table_count = count($table_list);
            $progress    = $this->cli->progress()->total($table_count);
            $i = 0;
            foreach($table_list as $table)
            {
                $progress->advance(1, '<light_green>('.$i.' of '.$table_count.') Dumping '.$table['Tables_in_'.$config->db].'</light_green>');
                exec('/Applications/MAMP/Library/bin/mysqldump --user='.$config->user.' --password='.$config->pass.' --host='.$config->host.' '.$config->db.' '.$table['Tables_in_'.$config->db].' | gzip > dump/'.$table['Tables_in_'.$config->db].'.sql.gz', $output);
                $i++;
            }
        }
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

        $this->out('completed', 'success');
        
        // foreach($tables as $table)
        // {
        //     exec('/Applications/MAMP/Library/bin/mysqldump --user=... --password=... --host=... ... | gzip > dumpzip2/'.$table->Tables_in_DATABASENAME.'.sql.gz', $output);
        // }
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
