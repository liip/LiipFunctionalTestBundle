# Upgrade guide from 2.x to 3.x

## Needed actions
This is the list of actions that you need to take when upgrading this bundle from the 2.x to the 3.x version:

 * Fixtures loading has been moved to a separate bundle named [LiipTestFixturesBundle](LiipTestFixturesBundle)
   If you need to load fixtures, follow the [install guide for LiipTestFixturesBundle](LiipTestFixturesBundle installation)
   And use `Liip\TestFixturesBundle\Test\FixturesTrait;` in tests classes
   
[LiipTestFixturesBundle]: https://github.com/liip/LiipTestFixturesBundle
[LiipTestFixturesBundle installation]: https://github.com/liip/LiipTestFixturesBundle/blob/master/doc/installation.md
