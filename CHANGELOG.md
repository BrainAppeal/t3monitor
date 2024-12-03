# CHANGELOG.md

## 2.1.0 (2024-12-03)

- [FEATURE] add TYPO3 13.4 LTS compatibility

## 2.0.6 (2024-06-13)

- [BUGFIX] fix usage of service registry if EXT:reports is not installed

## 2.0.5 (2024-06-13)

- [TASK] improve creation of status reports for TYPO3 12

## 2.0.4 (2024-01-16)

- [BUGFIX] fix null exception when calling status report checks with eID call
- [TASK] refactor extension list report
- [TASK] remove check for changed extension files (does not work in modern extensions)

## 2.0.3 (2023-10-13)

- [TASK] Remove output of exception details
- [TASK] Remove deprecated internal logger class

## 2.0.2 (2023-10-06)

- [TASK] Improve exception handling
- [TASK] Skip initialization of TSFE, when it is not needed

## 2.0.1 (2023-09-29)

- [TASK] Improve TSFE initialization for TYPO3 11 and 12

## 2.0.0 (2023-05-08)

- [FEATURE] Added support for TYPO3 12 LTS (12.4)
- [TASK] Breaking: Removed support for TYPO3 4.5 - 9.5 (since it's highly unlikely, that any extension updates will be released for these TYPO3 versions)

## 1.2.14 (2023-01-09)

- [BUGFIX] fix undefined array key in directory size calculation

## 1.2.14 (2022-12-21)

- [BUGFIX] fix var path retrieval for TYPO3 < 9

## 1.2.13 (2022-11-24)

- [BUGFIX] fix reading last log lines for empty log file

## 1.2.12 (2022-11-26)

- [BUGFIX] always add fallback report data, even if reports extension is installed
- [BUGFIX] always set current TYPO3 version in report
- [BUGFIX] fix undefined array key warning in Dispatcher

## 1.2.11 (2022-09-09)

- [FEATURE] show last 50 log entries from var/log; show directory sizes of var subdirectories

## 1.2.10 (2022-05-06)

- [TASK] catch report exceptions and add exception report in result

## 1.2.9 (2022-04-11)

- [BUGFIX] set NullOutput reference for update wizards implementing ChattyInterface
- [BUGFIX] keep custom reports, even if core reports are not empty

## 1.2.8 (2022-02-11)

- [FEATURE] update report checks for TYPO3 >= 10.4

## 1.2.7 (2021-11-08)

- [FEATURE] add TYPO3 11.5 compatibility
- [BUGFIX] replace array keys of result data that can't be used as XML tag names in database schema info
- [TASK] refactor database schema update statement report
- [FEATURE] add install tool reports for database analyzer and upgrade wizards

## 1.2.6 (2021-06-15)

- [TASK] add extension-key in composer.json
- [TASK] fix for PHP 7.4: Array and string offset access syntax with curly braces is deprecated
- [TASK] set static extension icon path if file exists
- [FEATURE] add extension svg icon
