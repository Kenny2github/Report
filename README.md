# Report

Report is an extension that, very simply, allows reporting revisions, hence the name.

# This repository is archived.
The extension is now on [Wikimedia Gerrit][1] ([GitHub mirror][2]) ([Phabricator mirror][3])

## Installation

From the root directory of your wiki, run the following commands:
```
cd extensions
git clone https://github.com/Kenny2github/Report.git
```
Then add the following line to your LocalSettings.php:
```php
wfLoadExtension( 'Report' );
```
Finally, from the root directory of your wiki, run the following commands:
```
cd maintenance
php update.php
```
This will create the necessary tables that the extension needs.

## Usage

Once it's installed, it's in use! The extension adds links next to every revision for reporting them, all of which lead to Special:Report.

For admins, simply navigate to Special:HandleReports to view reports that need handling.

[1]: https://gerrit.wikimedia.org/r/admin/repos/mediawiki/extensions/Report
[2]: https://github.com/wikimedia/mediawiki-extensions-Report
[3]: https://phabricator.wikimedia.org/diffusion/EREP/
