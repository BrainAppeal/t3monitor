# CHANGELOG.md

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
