### moodle-repository_dspace

A repository plugin developed to integrate Moodle with Dspace.

A plugin get resources from dspace repository by using REST API and add repositories into the Moodle courses
as a link or in the form of downloaded file.

###  Installation
* Uplaod dspace plugin zip file on plugin installation page Administration > Site administration > Plugins > Install plugins
* Go to the Manage repositories and enable the DSpace repository
* Configure the plugin by adding REST ENDPOINT URL and your custom repository name which will show in repositories list when you adding a file in to course

### How the repository plugin connect FG-LOR

 * After successful configure dspace repository you can add a link or download file form FG-LOR into Moodle. In order to communicate with FG-LOR, dspace plugin will establish a curl based connection with dspace LOR server by using REST ENDPOINT URL which you used during plugin configuration

 * When user select a `filemanager` or `filepicker` of Moodle and click on `upload` button then repositories list will be shown, you need to select dspace repository

 * After selecting dspace repository a new interface will show and ask the keyword to search in FG-LOR 

 * If you input search keyword, plugin will request FG-LOR server to show filtered items as per the desired keyword \
   END point: FG-LOR/rest/filtered-items?query_field[]=dc.name&query_op[]=contains&query_val[]=keyword
                        
 * FG-LOR returns the filtered items(files) in REST format and dspace plugin will shows the list of files including image, filename, size & file type etc.

 * User can now select the file from the list, plugin will request a new bit-stream URL to FG-LOR
 and display a file. Now this file can be used as a link or it can be download from the FG-LOR into Moodle
