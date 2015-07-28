# MySQL Dumper
[![StyleCI](https://styleci.io/repos/39658695/shield)](https://styleci.io/repos/39658695)
Create MySQL dumps and upload to Dropbox/FTP

## Options
```bash
--dir=DIR                    The directory to output the mysql dumps [default: "archive"]
--keep-local                 Keep local dumps when backup is complete
--skip-remote                Skip uploading files to remote
--ignore-table=IGNORE-TABLE  Skip tables during dump (multiple values allowed)
--self-update                Update mysqldumper
```
