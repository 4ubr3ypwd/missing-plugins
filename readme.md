# Missing Plugins

![](http://g.recordit.co/vT5Kadw0Bv.gif)

Imagine this situation: You're version controlling your wp-content folder
and include plugins in your versioning. So, you deploy your site and all is well.
But, along the way, on your production site, you install a few plugins you discovered
you needed along the way.

Now, months later, you decide to work on your
site. You probably pull your database down using something like WP DB Migrate Pro.
So, you just pulled your new database down and updated to master (which probably hasn't changed).
You load your website up and things don't look right? WTF!?

Well, it turns out that you installed a few plugins but you didn't get those into
version control. Also, the plugin wasn't active and so a bunch of things broke. Great!

But, for those of you that had this plugin installed (and in version control),
the site froze right before it loaded anything and installed the plugin for you!
You run `git add .` and commit the code for the plugin you were missing. Awesome!

This plugin does that for you and helps save your butt and make things easier for
you to keep on coding without the headache of getting that plugin into version
control and re-activating the plugin (and maybe having to re-pull your DB).

## Only for WordPress.org Plugins

If you have plugins that are not in the WordPress.org repository, think
Gravity Forms, it will totally skip these. The addition of this feature is planned
for a future release (See #2).

In the case that a non WordPress.org plugin is detected as missing,
it will be deactivated and you won't get an opportunity to install it with
Missing Plugins.

## Disabling Missing Plugins

If you have a case where you want to keep Missing Plugins active but disable
it from `wp-config.php` use:

`define( 'DISABLE_MISSING_PLUGINS', true )`

This will keep Missing Plugins from running at all. Good for Production if you
don't want the site to freeze up if a plugin file is missing.

## Changelog

### 1.0

- Installs plugins that are active in the DB but files don't exist in `plugins` folder
