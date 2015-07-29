# MySQL Dumper
Create MySQL dumps and upload to Dropbox/FTP

## Options
```bash
	--dir=DIR                    The directory to output the mysql dumps [default: "archive"]
	--keep-local                 Keep local dumps when backup is complete
	--skip-remote                Skip uploading files to remote
	--ignore-table=IGNORE-TABLE  Skip tables during dump (multiple values allowed)
```

## Usage

#### Dropbox
mysqldumper.json
```json
{
  "mysqldump": "/path/to/mysqldump",
  "host": "HOSTNAME",
  "db":   "DATABASE",
  "user": "USER",
  "pass": "PASSWORD",
  "keepfor": "7 days",
  "driver": "dropbox",
  "dropbox": {
  	"accesstoken": "ACCESSTOKEN",
  	"appsecret": "APPSECRET"
  }
}
```

Visit https://www.dropbox.com/developers/apps and get your "App secret".

#### FTP
mysqldumper.json
```json
{
  "mysqldump": "/path/to/mysqldump",
  "host": "HOSTNAME",
  "db":   "DATABASE",
  "user": "USER",
  "pass": "PASSWORD",
  "keepfor": "7 days",
  "driver": "ftp",
  "ftp": {
    "host": "ftp.domain.com",
    "username": "USERNAME",
    "password": "PASSWORD"
  }
}
```

Visit https://www.dropbox.com/developers/apps and get your "App secret".
