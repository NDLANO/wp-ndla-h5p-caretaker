=== NDLA's H5P Caretaker ===
Contributors: explorendla, otacke
Tags: h5p, accessibility, licensing
Requires at least: 4.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1.0
License: MIT
License URI: https://github.com/NDLANO/wp-ndla-h5p-caretaker/blob/master/LICENSE

Allows to check H5P files for improvements

== Description ==
The "H5P Caretaker" plugin for WordPress allows you to use NDLA's library of the same name to check H5P content files for improvement options, e.g. accessibility issues, conflicting licenses across subcontents or images that take too much storage space for their respective purpose.

== Installation ==

Install the _NDLA's H5P Caretaker_ plugin via the [WordPress Plugin directory](https://wordpress.org/plugins/ndla-h5p-caretaker/).

== Configure ==
Set the capability _use-h5p-caretaker_ as required. Only users/roles with this capability will be able to use the H5P Caretaker unless it is configured to be public. Please note that while WordPress has a fully fledged role management features, you still require separate plugins to assing capabilities to user roles (e. g. [User Role Editor](https://wordpress.org/plugins/user-role-editor/))

Go to the settings _Settings > H5P Caretaker_ and

- choose the URL where the tool should be made available,
- choose whether the tool should be usable publicly or only by users with the respective
  capability (not public by default),
- add introductory text that should be displayed on the page on top of the upload button, and
- add footer text that should be displayed at the bottom of the page.

== Use ==
The plugin will set up the URL _<your-wordpress-site>/h5p-caretaker_ or the URL that you configured in the settings. Go there, upload an H5P file and check the report for potential improvements of the content.
The plugin will also add an "H5P Caretaker" menu item to the tools menu. You will also find an "H5P Caretaker" button above the H5P content view inside the editor. Click on that to open the H5P Caretaker page and the respective file will be checked directly.

== Privacy ==
Please note that the uploaded H5P file will be removed immediately after analyzing it. It will not be stored permanently or used for anything else.
Please also note that the plugin will fetch the [H5P accessibility reports](https://studio.libretexts.org/help/h5p-accessibility-guide) and displaythose inside the report if appropriate. No personal information is shared in that process.

== Screenshots ==

1. You will receive an extensive report for your H5P contents.
2. You can change some options to your particular needs.

== Changelog ==

= 1.0.22 =
* Added optional NDLA branding.

= 1.0.21 =
* Fixed download report.
* Removed Filter option.

= 1.0.20 =
* Fixed old client version being used.
* Added code to remove obsolete client files.

= 1.0.19 =
* Got rid of long lists by introducing grouping by message type and using a carousel instead.
* Fixed accessibility issues in filter.
* Fixed reuse report
* Added license changes description to report
* Added French translation

= 1.0.18 =
* Fixed HTML endoding in intro/outro.
* Tested on WordPress 6.8.

= 1.0.17 =
* Simplify archive generation.

= 1.0.16 =
* Properly removed manual install zip from archive for good :-)

= 1.0.15 =
* Properly removed manual install zip from archive.

= 1.0.14 =
* Removed manual install zip from archive.

= 1.0.13 =
* Fixed sticky filter for many subcontents.

= 1.0.12 =
* Fixed optional intro/footer texts not showing up.
* Improved readmes.

= 1.0.11 =
First version released on the WordPress plugin directory.

== Upgrade Notice ==

= 1.0.22 =
No important reason to upgrade. But if you do, note that branding will be activated - but you can deactivate it in the
settings.

= 1.0.21 =
Upgrade to fix issue with report download.

= 1.0.20 =
Upgrade to use latest client.

= 1.0.19 =
Upgrade to get rid of some smaller bugs and to enjoy the updated visuals.

= 1.0.18 =
Upgrade if you need intro/outro with formatting.

= 1.0.17 =
Upgrade if you have time.

= 1.0.16 =
Upgrade if you have time.

= 1.0.15 =
Upgrade if you have time.

= 1.0.14 =
Upgrade if you have time.

= 1.0.13 =
Upgrade if you run into trouble with the filter and scrolling.

= 1.0.12 =
Upgrade if you require intro/footer texts.
