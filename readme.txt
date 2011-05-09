This Moodle plugin provides a way of copying admin config settings from one site to another. This can be done for backup purposes, or to assist with setting up a development environment that mimicks a live site.

It was created by John Beedell at the Open University.

To install using git, type this command in the root of your Moodle install
    git clone git://github.com/Beedell/moodle-report_configcopy.git admin/report/configcopy
Then add /local/codechecker to your git ignore.

Alternatively, download the zip from
    https://github.com/Beedell/moodle-report_configcopy/zipball/master
unzip it into the local folder, and then rename the new folder to codechecker.

After you have installed this local plugin , you should see a new option 
Site administration -> Reports -> Config tools in the settings block.

I hope you find this tool useful. Please feel free to enhance it.