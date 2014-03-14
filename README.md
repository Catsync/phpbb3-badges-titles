phpbb3-badges-titles
====================

A phpBB3 mod that adds user badges and titles.

#Installation:

##1. Copy files into the phpbb directory.

These are the files from the base PHPBB installation that have changes for this mod:

- viewtopic.php
- memberlist.php
- styles/prosilver/template/viewtopic_body.html
- styles/prosilver/template/memberlist_view.html
- styles/prosilver/theme/cp.css
- language/en/acp/common.php
	
The files are based on phpbb 3.0.7 PL1. If you have a different version, or have otherwise modded those files, 
you'll need to merge the changes by hand (there aren't many).
	
The rest of the files are new for this mod.
	
##2. Run install/badges.sql on your database. 

This was created with mysql in mind, it might not work with other databases.

##3. run install/install_permissions.php

In your browser, go to http://yourdomain/yourphpbb/install/install_permissions.php

This will do a few setup tasks, like create the permissions and some default configuration settings for the mod.


#HOW TO USE THIS MOD:

 Once it's installed, you should go to the **ACP->System->Module Management->Administration Control Panel** and
 add a new category for Badges, then add a sub category called "Badges and Titles" (this seems to be needed).
 Then add the Badges, Titles, and Config modules under there. Be sure to enable them all, and they should show up
 under a new tab in the ACP.
 
 Then, under **Module Management->User Control Panel**, add a category for Badges, and add Badge Management and Title
 Management. Shouldn't need the extra subcategory for the UCP.
 
 Note that you will have to upload badge images manually, there is no way to do it through the ACP for now.
 
 Set up the permissions for who can create badges, who can assign badges, and who can have badges. Titles
 use the same permissions as badges.
 
 