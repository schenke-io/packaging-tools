
## Installation

Install the package with composer:

```bash
  composer require schenke-io/packaging-tools
```

Add the setup command into `composer.json` under scripts.

```json
{
    
    
    "scripts": {
        "setup": "SchenkeIo\\PackagingTools\\Setup::handle"    
    }
    
}

```



Start the setup:

```bash
  composer setup
```

and check all available setup parameters:

```bash
  composer setup help
```

