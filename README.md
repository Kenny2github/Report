# Report

Report is an extension that, very simply, allows reporting revisions, hence the name.

## Installation

Run the following commands:
```
cd extensions
git clone https://github.com/Kenny2github/Report.git
```
Then add the following line to your LocalSettings.php:
```php
wfLoadExtension( 'Report' );
```

## Usage

Once it's installed, it's in use! The extension adds links next to every revision for reporting them, all of which lead to Special:Report.

For admins, simply navigate to Special:HandleReports to view reports that need handling.
