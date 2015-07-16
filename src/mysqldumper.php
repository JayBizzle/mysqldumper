<?php

namespace mysqldumper\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

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
        $dump_folder= getcwd().'/dump';
        $start_dump = date('YmdHi');
        $config     = json_decode(file_get_contents('config.json'));
        $adapter    = new Local($dump_folder);
        $filesystem = new Filesystem($adapter);
        $filesystem->createDir('/'.$start_dump);
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
                exec($config->mysqldump.' --user='.$config->user.' --password='.$config->pass.' --host='.$config->host.' '.$config->db.' '.$table['Tables_in_'.$config->db].' | gzip > '.$dump_folder.'/'.$start_dump.'/'.$table['Tables_in_'.$config->db].'.sql.gz', $output);
                $i++;
            }
        }
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

        $this->cli->br();
        $this->out('Completed', 'success');
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
