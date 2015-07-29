# MySQL Dumper
Create MySQL dumps and upload to Dropbox or remote FTP server.

## Options
```sh
  --dir=DIR                    The directory to output the mysql dumps [default: "archive"]
  --keep-local                 Keep local dumps when backup is complete
  --skip-remote                Skip uploading files to remote
  --ignore-table=IGNORE-TABLE  Skip tables during dump (multiple values allowed)
```

## Settings
Setting  | Description
------------- | -------------
mysqldump  | The full path to to `mysqldump`
host  | Database hostname
user | Database username
pass | Database password
keepfor | How long to keep the remote backups (any string that can be parsed by `strtotime` can be used)
driver | dropbox or ftp

**Dropbox specific settings**
Setting  | Description
------------- | -------------
accesstoken  | The full path to to `mysqldump`
appsecret  | Database hostname

Visit https://www.dropbox.com/developers/apps and get your "App Secret" and "Access Token".

**FTP specific settings**
Setting  | Description
------------- | -------------
ftphost | FTP hostname
ftpuser | FTP username
ftppass | FTP password
port | FTP Port (*Optional. Default `21`*)
root | FTP root path (*Optional. Default `./`*)
passive | Use passive mode (*Optional. Default `true`*)
ssl | Use SSL (*Optional. Default `true`*)
timeout | Connection timeout (*Optional. Default `30`*)

## Usage

#### Dropbox
`mysqldumper.json`
```json
{
  "mysqldump": "/path/to/mysqldump",
  "dbhost": "HOSTNAME",
  "dbname": "DATABASE",
  "dbuser": "USER",
  "dbpass": "PASSWORD",
  "keepfor": "7 days",
  "driver": "dropbox",
  "accesstoken": "ACCESSTOKEN",
  "appsecret": "APPSECRET"
}
```

Visit https://www.dropbox.com/developers/apps and get your "App Secret" and "Access Token".

#### FTP
`mysqldumper.json`
```json
{
  "mysqldump": "/path/to/mysqldump",
  "dbhost": "HOSTNAME",
  "dbname": "DATABASE",
  "dbuser": "USER",
  "dbpass": "PASSWORD",
  "keepfor": "7 days",
  "driver": "ftp",
  "ftphost": "ftp.domain.com",
  "ftpuser": "USERNAME",
  "ftppass": "PASSWORD"
}
```
