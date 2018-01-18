# Upgrade guide from 1.x to 2.0

## Needed actions
This is the list of actions that you need to take when upgrading this bundle from the 1.x to the 2.0 version:

 * Since 1.x uses `nelmio/alice` <= 2 and 2.0 switched to 3 with `theofidry/alice-data-fixtures`: 
```bash
composer remove --dev `nelmio/alice`
composer require --dev liip/functional-test-bundle:~2.0
```
