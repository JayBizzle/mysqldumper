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
        $this->loadConfig();
        $this->databaseSetup();

        $this->localAdapter = $this->setLocalAdapter();
        $this->remoteAdapter = $this->setRemoteAdapter();
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
        if (!$this->mysqldumpExists()) {
            $this->out('mysqldump not found. Please check your path.', 'error');
            die;
        }

        $this->archive_folder = date('YmdHi');
   
        $this->localAdapter->createDir($this->relativeDumpPath());

        $table_list = $this->listTables();

        $table_count = count($table_list);

        $progress = $this->cli->progress()->total($table_count);

        for ($i = 0; $i < $table_count; $i++) {
            $table_name = $table_list[$i]['Tables_in_'.$this->config->db];

            $progress->advance(1, $this->parseString('(%s of %s) Dumping %s', [($i+1), $table_count, $table_name], 'light_green'));

            $command = $this->buildCommand($table_name);
            exec($command);
        }

        $this->cli->br();
        $this->out('Completed', 'success');

        $this->deployToRemote();
    }

    public function relativeDumpPath()
    {
        return './dump/'.$this->archive_folder.'/';
    }

    public function fullDumpPath()
    {
        return getcwd().'/dump/'.$this->archive_folder.'/';
    }

    public function parseString($string, $params = [], $color = null) {
        if(empty($params)) {
            return $string;
        }

        if(!is_null($color)) {
            return '<'.$color.'>'.vsprintf($string, $params).'</'.$color.'>';
        }

        return vsprintf($string, $params);
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

    public function buildCommand($table_name)
    {
        $command_parts[] = $this->config->mysqldump;
        $command_parts[] = '--user='.$this->config->user;
        $command_parts[] = '--password='.$this->config->pass;
        $command_parts[] = '--host='.$this->config->host.' '.$this->config->db.' '.$table_name;
        $command_parts[] = '| gzip > "'.$this->fullDumpPath().$table_name.'.sql.gz"';

        return implode(' ', $command_parts);
    }

    public function deployToRemote()
    {
        $localPath = $this->relativeDumpPath();

        $files = $this->localAdapter->listContents($localPath);

        foreach ($files as $file) {
            $contents = $this->localAdapter->read($localPath.$file['basename']);

            $fileSize = $this->localAdapter->getSize($localPath.$file['basename']);

            $this->out($this->parseString('Uploading %s (%s)', [$file['basename'], $fileSize], 'light_green'));
            $this->remoteAdapter->write($localPath.$file['basename'], $contents);
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
        return new Filesystem(new Local($this->dump_folder));
    }

    public function setRemoteAdapter()
    {
        return $this->{'create'.ucfirst($this->config->driver).'Driver'}();
    }

    public function createDropboxDriver()
    {
        $client = new Client($this->config->dropbox->accesstoken, $this->config->dropbox->appsecret);
        $adapter = new DropboxAdapter($client);
        return new Filesystem($adapter);
    }
}
