<?php

namespace Gradcracker\Console\Command;

use Aws\S3\S3Client;
use Dropbox\Client;
use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use League\CLImate\CLImate;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MySQLDumperCommand extends Command
{
    const MANIFEST_FILE = 'http://jaybizzle.github.io/mysqldumper/manifest.json';

    /**
     * The cli instance.
     * 
     * @var League\CLImate\CLImate
     */
    protected $cli;

    /**
     * The loaded config.
     * 
     * @var object
     */
    protected $config;

    /**
     * Local filesystem adapter.
     * 
     * @var League\Flysystem\Filesystem
     */
    protected $localAdapter;

    /**
     * Remote filesystem adapter.
     * 
     * @var League\Flysystem\Filesystem
     */
    protected $remoteAdapter;

    /**
     * The database connection.
     * 
     * @var \PDO
     */
    protected $db;

    /**
     * The dated archive output folder.
     * 
     * @var string
     */
    protected $archive_folder;

    /**
     * Keep local copies of dumps.
     * 
     * @var bool
     */
    protected $keep_local = false;

    /**
     * Skip the remote upload.
     * 
     * @var bool
     */
    protected $skip_remote = false;

    /**
     * Array of tables to ignore.
     * 
     * @var array
     */
    protected $ignore_table = [];

    /**
     * Create a new mysqldumper instance.
     * 
     * @param League\CLImate\CLImate $cli
     */
    public function __construct(CLImate $cli)
    {
        parent::__construct();
        $this->cli = $cli;
    }

    /**
     * Configure the command.
     * 
     * @return void
     */
    protected function configure()
    {
        $this->setName('mysqldumper')->setDescription('Dump the data')
             ->addOption(
                'dir',
                null,
                InputOption::VALUE_REQUIRED,
                'The directory to output the mysql dumps',
                'archive'
            )->addOption(
                'keep-local',
                null,
                InputOption::VALUE_NONE,
                'Keep local dumps when backup is complete'
            )->addOption(
                'skip-remote',
                null,
                InputOption::VALUE_NONE,
                'Skip uploading files to remote'
            )->addOption(
                'ignore-table',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Skip tables during dump'
            )->addOption(
                'self-update',
                null,
                InputOption::VALUE_NONE,
                'Check for, and update to latest version'
            );
    }

    /**
     * Excute the command.
     * 
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // setup options
        $this->dump_folder = $input->getOption('dir');
        $this->keep_local = $input->getOption('keep-local');
        $this->skip_remote = $input->getOption('skip-remote');
        $this->ignore_table = $input->getOption('ignore-table');

        $this->config = $this->loadConfig();

        $this->db = $this->databaseSetup();

        $this->localAdapter = $this->setLocalAdapter();
        $this->remoteAdapter = $this->setRemoteAdapter();

        if ($input->getOption('self-update')) {
            $this->update();
        } else {
            $this->mysqldumper();
        }
    }

    public function update()
    {
        $manager = new Manager(Manifest::loadFile(self::MANIFEST_FILE));
        if ($manager->update($this->getApplication()->getVersion(), true)) {
            $this->out('Updated to latest version!', 'success');
        } else {
            $this->out('mysqldumper up-to-date', 'warning');
        }
    }

    /**
     * The body of the command.
     * 
     * @return void
     */
    public function mysqldumper()
    {
        if (!$this->mysqldumpExists()) {
            $this->out('mysqldump not found. Please check your path.', 'error');
            die;
        }

        // Set the name of the dated archive folder
        $this->archive_folder = date('YmdHi');

        // Create the output folder
        $this->localAdapter->createDir($this->relativeDumpPath());

        // Get a list of the tables we are going to dump
        $table_list = $this->listTables();

        // Count tables
        $table_count = count($table_list);

        // Create a progress bar
        $progress = $this->cli->progress()->total($table_count);

        // Loop of tables and create dump
        for ($i = 0; $i < $table_count; $i++) {
            $table_name = $table_list[$i]['Tables_in_'.$this->config->db_name];

            $progress->advance(1, $this->parseString('(%s of %s) Dumping %s', [($i + 1), $table_count, $table_name], 'light_green'));

            $command = $this->buildCommand($table_name);
            exec($command);
        }

        $this->cli->br();
        $this->out('Dump complete', 'success');

        if (!$this->skip_remote) {
            $this->out('Uploading to remote', 'success');
            $this->deployToRemote();
        } else {
            $this->out('Skipping remote upload', 'warning');
        }

        // Clean up
        $this->cleanupLocal();
        $this->cleanupRemote();
    }

    /**
     * Clean up local files.
     * 
     * @return void
     */
    public function cleanupLocal()
    {
        if (!$this->keep_local) {
            $local_path = $this->relativeDumpPath();

            $this->localAdapter->deleteDir($local_path);
        }
    }

    /**
     * Clean up remote files.
     * 
     * @return void
     */
    public function cleanupRemote()
    {
        if (isset($this->config->keepfor)) {
            $remotePath = './'.$this->dump_folder;

            $directories = $this->remoteAdapter->listContents($remotePath);

            $timestamp = date('YmdHi', (time() - (time() - strtotime($this->config->keepfor.' ago'))));

            foreach ($directories as $dir) {
                if ($dir['filename'] < $timestamp) {
                    $this->remoteAdapter->deleteDir($dir['path']);
                }
            }
        }
    }

    /**
     * Return a path relative to the .phar root.
     * 
     * @return string
     */
    public function relativeDumpPath()
    {
        return $this->dumpPath(true);
    }

    /**
     * Return the path to the dump folder.
     * 
     * @param bool $relative
     *
     * @return string
     */
    public function dumpPath($relative = false)
    {
        $basePath = '/'.$this->dump_folder.'/'.$this->archive_folder.'/';
        if ($relative) {
            return '.'.$basePath;
        } else {
            return getcwd().$basePath;
        }
    }

    /**
     * Parse strings with passed variables.
     * 
     * @param string $string
     * @param array  $params
     * @param string $color
     *
     * @return string
     */
    public function parseString($string, $params = [], $color = null)
    {
        if (empty($params)) {
            return $string;
        }

        if (!is_null($color)) {
            return '<'.$color.'>'.vsprintf($string, $params).'</'.$color.'>';
        }

        return vsprintf($string, $params);
    }

    /**
     * Output messages to the terminal.
     * 
     * @param string $message
     * @param string $style
     *
     * @return void
     */
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
            case 'warning':
                $this->cli->yellow($message);
                break;
        }
    }

    /**
     * Build the mysqldump command.
     * 
     * @param string $table_name
     *
     * @return string
     */
    public function buildCommand($table_name)
    {
        $command_parts[] = $this->config->mysqldump;
        $command_parts[] = '--user='.$this->config->db_user;
        $command_parts[] = '--password=\''.$this->config->db_pass.'\'';
        $command_parts[] = '--host='.$this->config->db_host.' '.$this->config->db_name.' '.$table_name;
        $command_parts[] = '| gzip > "'.$this->dumpPath().$table_name.'.sql.gz"';

        return implode(' ', $command_parts);
    }

    /**
     * Deploy files to the remote filesystem.
     * 
     * @return void
     */
    public function deployToRemote()
    {
        $local_path = $this->relativeDumpPath();

        $files = $this->localAdapter->listContents($local_path);

        foreach ($files as $file) {
            $contents = $this->localAdapter->readStream($local_path.$file['basename']);

            $file_size = $this->localAdapter->getSize($local_path.$file['basename']);

            $this->out($this->parseString('Uploading %s (%s)', [$file['basename'], $this->formatBytes($file_size)], 'light_green'));
            $this->remoteAdapter->writeStream($local_path.$file['basename'], $contents);
        }
    }

    /**
     * Load config.
     * 
     * @return void
     */
    public function loadConfig()
    {
        if (!file_exists('mysqldumper.json')) {
            $this->out('No mysqldumper.json found', 'error');
            die;
        } else {
            return json_decode(file_get_contents('mysqldumper.json'));
        }
    }

    /**
     * Setup a PDO connection to the database.
     * 
     * @return void
     */
    public function databaseSetup()
    {
        try {
            $conn = new \PDO('mysql:host='.$this->config->db_host.';dbname='.$this->config->db_name, $this->config->db_user, $this->config->db_pass);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $conn;
        } catch (PDOException $e) {
            echo 'Error: '.$e->getMessage();
        }
    }

    /**
     * Get all tables in the database.
     * 
     * @return array
     */
    public function listTables()
    {
        $query = $this->buildQuery();

        $stmt = $this->db->prepare($query);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC); // set the resulting array to associative
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Build the list tables query.
     * 
     * @return string
     */
    public function buildQuery()
    {
        $query = 'show tables';

        if (!empty($this->ignore_table)) {
            $query_parts[] = $query;
            $query_parts[] = 'where Tables_in_'.$this->config->db_name;
            $query_parts[] = 'not in ("'.implode('","', $this->ignore_table).'")';

            return implode(' ', $query_parts);
        } else {
            return $query;
        }
    }

    /**
     * Check if the mysqldump command path exists.
     * 
     * @return bool
     */
    public function mysqldumpExists()
    {
        return file_exists($this->config->mysqldump);
    }

    /**
     * Set the local filesystem adapter.
     *
     * @return League\Flysystem\Filesystem
     */
    public function setLocalAdapter()
    {
        return new Filesystem(new Local('./'));
    }

    /**
     * Create an instance of the specified remote adapter.
     *
     * @return League\Flysystem\Filesystem
     */
    public function setRemoteAdapter()
    {
        return $this->{'create'.ucfirst($this->config->driver).'Driver'}();
    }

    /**
     * Create Dropbox connection.
     * 
     * @return League\Flysystem\Filesystem
     */
    public function createDropboxDriver()
    {
        $client = new Client($this->config->accesstoken, $this->config->appsecret);
        $adapter = new DropBoxCerts($client);

        $adapter->useExternalPaths();

        return new Filesystem($adapter);
    }

    /**
     * Create FTP connection.
     * 
     * @return League\Flysystem\Filesystem
     */
    public function createFtpDriver()
    {
        $adapter = new FTP([
            'host'     => $this->config->ftp_host,
            'username' => $this->config->ftp_user,
            'password' => $this->config->ftp_pass,

            // optional config settings
            'port'    => $this->config->ftp_port ?: 21,
            'root'    => $this->config->ftp_root ?: './',
            'passive' => $this->config->ftp_passive ?: true,
            'ssl'     => $this->config->ftp_ssl ?: true,
            'timeout' => $this->config->ftp_timeout ?: 30,
        ]);

        return new Filesystem($adapter);
    }

    /**
     * Create Amazon S3 connection.
     * 
     * @return League\Flysystem\Filesystem
     */
    public function createS3Driver()
    {
        $client = new S3Client([
            'credentials' => [
                'key'    => $this->config->s3_key,
                'secret' => $this->config->s3_secret,
            ],
            'region'  => $this->config->s3_region,
            'version' => 'latest',
        ]);

        $adapter = new AwsS3Adapter($client, $this->config->s3_bucket);

        return new Filesystem($adapter);
    }

    /**
     * Format bytes to a human readable size.
     * 
     * @param int $bytes
     *
     * @return string
     */
    public function formatBytes($bytes)
    {
        return \ByteUnits\Binary::bytes($bytes)->format();
    }
}

class DropBoxCerts extends DropboxAdapter
{
    /**
     * Normally, the Dropbox SDK tells cURL to look in the "certs" folder for root certificate
     * information. But this won't work if the SDK is running from within a PHAR because
     * cURL won't read files that are packaged in a PHAR.
     * 
     * @return void
     */
    public function useExternalPaths()
    {
        \Dropbox\RootCertificates::useExternalPaths();
    }
}
