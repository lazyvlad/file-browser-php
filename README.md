# file-browser-php
Browse a folder relative to your root folder.

Code of interest is in index.php
app.php and api.php are here for some backward compatability
## API parameters

 - folder (String)  -  string that represents the folder relative to the ROOT of the app (relative to index.php)
 - depth_search (Boolean) 0 will do no recursion and will return only the top folder, 1 will go into the subfolders until it scans all of them
 - cut_date (Integer) UNIX timestamp in seconds
 - cut_date_end (Integer) UNIX timestamp in seconds
 - direction (String) if older return the older first

## Config parameters
- entry_points
- ignore_file_list
- ignore_ext_list
- sort_by


