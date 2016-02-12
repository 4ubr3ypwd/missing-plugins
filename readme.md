# Missing Plugins

Imagine this situation: You're version controlling your wp-content folder
and include plugins in your versioning. So, you deploy your site and all is well.
But, along the way, on your production site, you install a few plugins you discovered
you needed along the way.

Now, months later, you decide to work on your
site. You probably pull your database down using something like WP DB Migrate Pro.
So, you just pulled your new database down and updated to master (which probably hasn't changed).
You load your website up and things don't look right? WTF!?

Well, it turns out that you installed a few plugins but you didn't get those into
version control. Also, the plugin wasn't active and so a bunch of things broke.

Great!

But, for those of you that had this plugin installed (and in version control),
the site froze right before it loaded anything and installed the plugin for you!
You run `git add .` and commit the code for the plugin you were missing.

Awesome!

This plugin does that for you and helps save your butt and make things easier for
you to keep on coding without the headache of getting that plugin into version
control and re-activating the plugin (and maybe having to re-pull your DB).

![](http://g.recordit.co/vT5Kadw0Bv.gif)



