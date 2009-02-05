=== ICanLocalize Comment Translator ===
Contributors: ICanLocalize
Tags: i18n, translation, localization, language, multilingual, SitePress
Requires at least: 2.6
Tested up to: 2.7
Stable tag: 1.3.1

Manages human translated blogs and allows you to administrate, create contents and moderate comments in your native language.

== Description ==

This plugin receives the translated contents and stores in the translated language blog(s). Then, it allows moderating comments and replying to them in your native language.

"ICanLocalize Comment Translator" works together with [ICanLocalize Translator Plugin](http://wordpress.org/extend/plugins/icanlocalize-translator/).
It needs to be installed in the translated-language blog(s), while ICanLocalize Translator is installed in the original language blog.

Together, these two plugins allow running professionally translated multi-lingual WordPress websites.

= Comment moderation =
When visitors read comments in their language, it's most likely that they'll leave comments in that language too.
The plugin will automatically translate these comments to your language, so that you can easily moderate and respond to them.

You can reply in your own language. Then, your replies will be professionally translated and posted back in the language of the translated blog.
Your visitors will see everything in their language and you see everything in your.

= Where can I see some examples? =

[Our own blog](http://blog-en.icanlocalize.com) is being translated by our system from English to French.
You're invited to [contact us](http://www.icanlocalize.com/web_dialogs/new?language_id=1&store=4) for other examples.

= SitePress =
This plugin is part of [SitePress](http://sitepress.org) - a collection of plugins that turn WordPress into a fully featured multilingual content management system.

== Installation ==

ICanLocalize Comment Translator follows the standard plugin install:

   1. Unzip the "ICanLocalize Comment Translator" archive and copy the folder to /wp-content/plugins/.
   2. Activate the plugin.
   3. Use the Settings > ICanLocalize Comment Translator admin page to enter your website ID and accesskey and select default translation options.

== Frequently Asked Questions ==

= Where do I get my website ID and accesskey to start using the plugin? =

You will need to visit [ICanLocalize](http://www.icanlocalize.com) and sign up for an account (it's free). Then, create a new "CMS translation" project.
This plugin must already be installed on your blog in order for our system to connect to it and validate the installation.

Then, your website's ID and accesskey are generated. Enter to the plugin admin screen and you're ready to start.

= Is this free translation? =

No. We pay professional human translators for their work, so we must charge for it too.
The payment for the translation will be negotiated directly between you and the translator (using our system).

= How good are these translation? =
All the translators in our system are professional translators, writing in their native language.
You can also select to use your own translator, if you're already working with one.
We guarantee that all translations done by our own translators are excellent.

= How do I localize my theme? =
This plugin will not handle your theme localization - but we can certainly help with this too. You'll need to follow these steps:

* Wrap all texts in the theme in gettext calls (this is what WP is doing for itself too).
* Create a PO or POT file that includes all texts to be translated. You're welcome to our free online [.PO extractor from PHP](http://www.icanlocalize.com/tools/php_scanner) which will read the ZIP file containing your theme and produce a single .PO file.
* Send the PO file to translation. Again, [our translators](http://www.icanlocalize.com) can do this job for you.
* Save the MO files you get from the translator in the theme folder.

= Will this plugin change my database or break things up? =
Absolutely not. It doesn't change the default WordPress tables. Instead, this plugin will create new contents in different blogs, where the translations will be kept.
All linking information between the contents in different languages is simply stored as custom fields.

= How do I add language selectors between the translated and original language blog? =
Follow these instructions for [adding language selectors](http://sitepress.org/wordpress-translation/automatic-links-between-original-and-translations/).
You can add a drop down language selector in header.php or a list of available languages at the beginning or end of each post/page.

== Version History ==

* Version 0.2
	* First public release
* Version 1.0
	* Bug fixes
* Version 1.2
	* Auto adjusts links to translated pages and posts.
	* Added online 'getting started' guide.
* Version 1.3
	* Includes drop down language switcher that can be added to header.php.
* Version 1.3.1
	* Dropdown language selector now support IE6
