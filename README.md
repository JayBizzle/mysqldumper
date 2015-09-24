# MySQL Dumper

[![StyleCI](https://styleci.io/repos/39658695/shield)](https://styleci.io/repos/39658695)

Create MySQL dumps and upload to Dropbox, remote FTP server or Amazon S3.

## Installation
Get the latest version of `mysqldumper.phar`. Use cURL to grab it directly on your server, or download the latest binary from [Releases](https://github.com/JayBizzle/mysqldumper/releases).
```sh
curl -OL https://github.com/JayBizzle/mysqldumper/releases/download/1.0.5/mysqldumper.phar
```
Then create a `mysqldumper.json` in the same directory as the phar and enter your own settings.


## Options
```sh
  --dir=DIR                    The directory to output the mysql dumps [default: "archive"]
  --keep-local                 Keep local dumps when backup is complete
  --skip-remote                Skip uploading files to remote
  --ignore-table=IGNORE-TABLE  Skip tables during dump (multiple values allowed)
  --self-update                Check for, and update to latest version
```


## Settings
Setting  | Description
------------- | -------------
mysqldump  | The full path to to `mysqldump`
db_host  | Database hostname
db_user | Database username
db_pass | Database password
keepfor | How long to keep the remote backups (any string that can be parsed by `strtotime` can be used)
driver | dropbox, ftp or s3


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


**Amazon S3 specific settings**

Setting  | Description
------------- | -------------
s3_bucket | Bucket name
s3_key | Your key
s3_secret | Your secret
s3_region | S3 region

[See here](http://www.cloudberrylab.com/blog/how-to-find-your-aws-access-key-id-and-secret-access-key-and-register-with-cloudberry-s3-explorer/) to find out how to obtain you S3 key and secret.


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

#### Amazon S3
`mysqldumper.json`
```json
{
  "mysqldump": "/path/to/mysqldump",
  "db_host": "HOSTNAME",
  "db_name": "DATABASE",
  "db_user": "USER",
  "db_pass": "PASSWORD",
  "keepfor": "7 days",
  "driver": "s3",
  "s3_bucket": "BUCKETNAME",
  "s3_key": "KEY",
  "s3_secret": "SECRET",
  "s3_region": "REGION"
}
```

[See here](http://www.cloudberrylab.com/blog/how-to-find-your-aws-access-key-id-and-secret-access-key-and-register-with-cloudberry-s3-explorer/) to find out how to obtain you S3 key and secret.
