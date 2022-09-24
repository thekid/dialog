# Dialog photoblog

[![Build status on GitHub](https://github.com/thekid/dialog/workflows/Tests/badge.svg)](https://github.com/thekid/dialog/actions)
[![Uses XP Framework](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)

See https://dialog.sloppy.zone/

## Importing local directories

```bash
$ xp import import-target/ http://user:pass@localhost:8080/api
```

### Content

Expects the following directory structure:

```
import-target
|- content.md
|- image-1.jpg
|- image-2.jpg
`- image-(n).jpg
```

### Journey

Expects the following directory structure:

```
import-target
|- journey.md
|- part-1
|  |- content.md
|  |- image-1.jpg
|  `- image-2.jpg
|- part-2
|  |- content.md
|  |- image-1.jpg
|  `- image-2.jpg
`- part-(n)
```