<?php

namespace Gradcracker\Console\Command;

use Dropbox\Client;
use League\CLImate\CLImate;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Dropbox\DropboxAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class mysqldumper extends Command
{
    protected $cli;
 
    protected $config;

    protected $localAdapter;

    protected $remoteAdapter;

    protected $db;

    public function __construct(CLImate $cli)
    {
        parent::__construct();
        $this->cli = $cli;
        $this->dump_folder = getcwd().'/dump';
        $this->loadConfig();
        $this->databaseSetup();
        $this->setLocalAdapter();
        $this->setRemoteAdapter();
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
        if ($this->mysqldumpExists()) {
            $start_dump = date('YmdHi');
       
            $this->localAdapter->createDir('/'.$start_dump);

            $table_list = $this->listTables();

            $table_count = count($table_list);

            $progress = $this->cli->progress()->total($table_count);

            for ($i = 0; $i < $table_count; $i++) {
                $progress->advance(1, '<light_green>('.$i.' of '.$table_count.') Dumping '.$table_list[$i]['Tables_in_'.$this->config->db].'</light_green>');
                exec($this->config->mysqldump.' --user='.$this->config->user.' --password='.$this->config->pass.' --host='.$this->config->host.' '.$this->config->db.' '.$table_list[$i]['Tables_in_'.$this->config->db].' | gzip > "'.$this->dump_folder.'/'.$start_dump.'/'.$table_list[$i]['Tables_in_'.$this->config->db].'.sql.gz"');
            }

            $this->cli->br();
            $this->out('Completed', 'success');
        } else {
            $this->out('mysqldump not found. Please check your path.', 'error');
        }
        $files = $this->localAdapter->listContents('./'.$start_dump);

        foreach ($files as $file) {
            $contents = $this->localAdapter->read('./'.$start_dump.'/'.$file['basename']);
            $this->out('Uploading '.$file['basename'].' ('.$this->localAdapter->getSize('./'.$start_dump.'/'.$file['basename']).')', 'success');
            $this->remoteAdapter->write('./'.$start_dump.'/'.$file['basename'], $contents);
        }
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

    public function loadConfig()
    {
        $this->config = json_decode(file_get_contents('config.json'));
    }

    public function databaseSetup()
    {
        try {
            $conn = new \PDO("mysql:host=".$this->config->host.";dbname=".$this->config->db, $this->config->user, $this->config->pass);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $this->db = $conn;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function listTables()
    {
        $stmt = $this->db->prepare('SHOW TABLES');
        $stmt->setFetchMode(\PDO::FETCH_ASSOC); // set the resulting array to associative
        $stmt->execute();
            
        return $stmt->fetchAll();
    }

    public function mysqldumpExists()
    {
        return file_exists($this->config->mysqldump);
    }

    public function setLocalAdapter()
    {
        $this->localAdapter = new Filesystem(new Local($this->dump_folder));
    }

    public function setRemoteAdapter()
    {
        $client = new Client($this->config->dropbox->accesstoken, $this->config->dropbox->appsecret);
        $adapter = new DropboxAdapter($client);
        $this->remoteAdapter = new Filesystem($adapter);
    }
}
