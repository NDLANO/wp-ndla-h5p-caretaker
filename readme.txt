=== NDLA's H5P Caretaker ===
Contributors: otacke
Tags: h5p, accessibility, licensing
Requires at least: 4.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0
License: MIT
License URI: https://github.com/NDLANO/wp-ndla-h5p-caretaker/blob/master/LICENSE

Allows to check H5P files for improvements

== Description ==
The "H5P Caretaker" plugin for WordPress allows you to use NDLA's library of the same name to
check H5P content files for improvement options, e.g. accessibility issues, conflicting licenses
across subcontents or images that take too much storage space for their respective purpose.

== Install ==
=== Use WordPress Plugin Directory ===
Install the _NDLA's H5P Caretaker_ plugin via the WordPress Plugin directory (not available yet).

=== Upload ZIP file ===
1. Go to https://github.com/NDLANO/wp-ndla-h5p-caretaker/releases.
2. Pick the latest release (or the one that you want to use) and download the
   `wp-ndla-h5p-caretaker-<plugin-version>.zip` file.
3. Log in to your WordPress site as an admin and go to _Plugins > Add New Plugin_.
4. Click on the _Upload Plugin_ button.
5. Upload the ZIP file with the plugin code.
6. Activate the plugin.

== Configure ==
Set the capability _use-h5p-caretaker_ as required. Only users/roles with this
capability will be able to use the H5P Caretaker unless it is configured to be public.
Please note that while WordPress has a fully fledged role management features, you still
require separate plugins to assing capabilities to user roles
(e. g. [User Role Editor](https://wordpress.org/plugins/user-role-editor/))

Go to the settings _Settings > H5P Caretaker_ and
- choose the URL where the tool should be made available,
- choose whether the tool should be usable publicly or only by users with the respective
  capability (not public by default),
- add introductory text that should be displayed on the page on top of the upload button, and
- add footer text that should be displayed at the bottom of the page.

== Use ==
The plugin will set up the URL _<your-wordpress-site>/h5p-caretaker_ or the URL that you configured in
the settings. Go there, upload an H5P file and check the report for potential improvements of the
content.
The plugin will also add an "H5P Caretaker" menu item to the tools menu.
You will also find an "H5P Caretaker" button above the H5P content view inside the editor. Click on that to open the
H5P Caretaker page and the respective file will be checked directly.

== Privacy ==
Please note that the uploaded H5P file will be removed immediately after analyzing it. It will not be stored permanently
or used for anything else.
Please also note that the plugin will fetch the H5P accessibility reports from
https://studio.libretexts.org/help/h5p-accessibility-guide and displaythose inside the report if appropriate. No
personal information is shared in that process.
