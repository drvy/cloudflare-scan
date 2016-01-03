# CloudFlareScan CLI

A simple console line client for the **CloudFlareScan** PHP Class. Its designed tu run on Terminals (Command Line for Windows). Make sure to have the 'CloudFlareScan.php' in the correct folder (../src).

![CLI Example](https://raw.githubusercontent.com/drvy/CloudFlareScan/master/cli/cli_example.jpg "CLI Example")

## Usage

    php -f cloudflarescan.php --url=example.com --update --list=list.txt

## Options
| parameter | Description |
| :-------: |:------------|
| --help | Displays a help screen with usefull information. |
| --url= | Sets the url to scan. (example.com). |
| --update | The script will try to update CloudFlare's IPs via Internet. |
| --list= | Provide a list of subdomains to scan.|

**NOTICE:** ***--list*** argument requires a plain-text file with the names of the sub-domains to check. Those sub-domains must be separated by a new line each and cannot contain symbols, dots or similar characters. An example for such a file would be:

    customsubdomain
    www
    webmail
    cpanel
    admin