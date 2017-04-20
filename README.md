# t3monitor
Monitoring multiple TYPO3 CMS Websites

Here you can find  the code of the TYPO3 Extension "t3monitor". To use the t3monitor an Online-Account is needed or you can get the self-hosted version für LAMP Servers.

Visit https://www.t3monitor.de and signup for a free 2 month test.

## Installation via Composer (TYPO3 7+)

Add the following lines to your composer.json that is generating the TYPO3 Installation.

```json
"require": {
	"brainappeal/t3monitor": "dev-master"
},
"repositories": {
	{
		"type": "vcs",
		"url": "https://github.com/BrainAppeal/t3monitor.git"
	},
}
```



