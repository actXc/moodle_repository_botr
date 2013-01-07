moodle_repository_botr
======================
repository for adding tags of BitsOnTheRun videos.

## Requirements:

* account at bitsontherun.com ( small accounts are free (true on 2013/Jan/06)
* filter installed
* filter settings filled with APi-key and Api-secret
* filter activated

You need the moodle_filter_botr 
https://github.com/actXc/moodle_filter_botr
plugin to see the videos.

## what for?

This repository helps to find a video and insert a link like <code>[botr 3hd45rhf]</code> into the the text.
After the filter has added a player and created a secret timeout-hash you can can see the video.

##Version:

beta test state,Do not use for productiv sites, do not expect anything

## todo: (Backlog) 

* get the real tags for search (big)
* find a way to control the capabilities, teachers can edit and delet all public repos (big)
* preview url in filepicker select window (the last window) (big)
* better meta data display in filepicker, more data, better useabillity=click where? (big)
* make install video (medium)
* make use video (small)
* announce in moodle forum (small)
* find a experienced developer for a code review (medium)
* switch to login instead of store key permanent
* add teachers private botr account (medium)

## feature:
+ supports all video quality offered by bitsontherun ( mobil, desktop, HTML5, flash )
+ no script or code embedding in the text, just a small tag like [botr 1234abcd]
+ compatibillity with any other botr api based application
+ use central API-credential together with filter
+ instances with pre selecting with tags
+ instances with player pre selection 
+ uses JW-player


