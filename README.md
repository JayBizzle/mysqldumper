# MySQL Dumper

[![StyleCI](https://styleci.io/repos/39658695/shield)](https://styleci.io/repos/39658695)

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
db_host  | Database hostname
db_user | Database username
db_pass | Database password
keepfor | How long to keep the remote backups (any string that can be parsed by `strtotime` can be used)
driver | dropbox or ftp


**Dropbox specific settings**

Setting  | Description
------------- | -------------
accesstoken  | Dropbox access token
appsecret  | Dropbox app secret

Visit https://www.dropbox.com/developers/apps and get your "App Secret" and "Access Token".


**FTP specific settings**

Setting  | Description
------------- | -------------
ftp_host | FTP hostname
ftp_user | FTP username
ftp_pass | FTP password
ftp_port | FTP Port (*Optional. Default `21`*)
ftp_root | FTP root path (*Optional. Default `./`*)
ftp_passive | Use passive mode (*Optional. Default `true`*)
ftp_ssl | Use SSL (*Optional. Default `true`*)
ftp_timeout | Connection timeout (*Optional. Default `30`*)


## Usage

#### Dropbox
`mysqldumper.json`
```json
{
  "mysqldump": "/path/to/mysqldump",
  "db_host": "HOSTNAME",
  "db_name": "DATABASE",
  "db_user": "USER",
  "db_pass": "PASSWORD",
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
  "db_host": "HOSTNAME",
  "db_name": "DATABASE",
  "db_user": "USER",
  "db_pass": "PASSWORD",
  "keepfor": "7 days",
  "driver": "ftp",
  "ftp_host": "ftp.domain.com",
  "ftp_user": "USERNAME",
  "ftp_pass": "PASSWORD"
}
```
