# MySQL Dumper
Create MySQL dumps and upload to Dropbox/FTP

## Options
```bash
	--dir=DIR                    The directory to output the mysql dumps [default: "archive"]
	--keep-local                 Keep local dumps when backup is complete
	--skip-remote                Skip uploading files to remote
	--ignore-table=IGNORE-TABLE  Skip tables during dump (multiple values allowed)
```

## Test without having to build the phar
```bash
php ./bin/mysqldumper env # env is just an example required argument
```

## Build the phar
`php box.phar build`
