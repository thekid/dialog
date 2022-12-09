Dialog change log
=================

## ?.?.? / ????-??-??

## 1.12.0 / 2022-12-09

* Upgrade to [PHP 8.2](https://www.php.net/archive/2022.php#2022-12-08-1)
  (@thekid)
* Merged PR #45: Extract image and video processing to *processing*
  package - a prerequisite for being able to reuse it for #44.
  (@thekid)
* Merged PR #43: Refactor to use injection library - @thekid

## 1.11.0 / 2022-11-19

* Merged PR #42: Collect statistics on views. Users must look at pages
  for a certain time before we count a view - a more honest statistic
  that also doesn't include web crawlers, for instance.
  (@thekid)

## 1.10.0 / 2022-11-13

* Merged PR #41: Change lightbox to use HTML `<dialog>` element - @thekid

## 1.9.0 / 2022-11-08

* Merged PR #40: Migrate to JS classes. The JS code base is now much
  cleaner and easier to maintain.
  (@thekid)

## 1.8.5 / 2022-11-07

* Fixed issue #39: Bullet points from autocompleter showing - @thekid

## 1.8.4 / 2022-11-05

* Fixed issue #37: Scroll links in light theme not readable - @thekid

## 1.8.3 / 2022-11-03

* Fixed errors in the browser console concerning REST API response
  headers - see xp-forge/rest-api#21
  (@thekid)
* Fixed accessibility warnings in the browser console concerning
  `role="list"` - first part of #35
  (@thekid)

## 1.8.2 / 2022-11-02

* Fixed issue #33: SVG mimetype - 'content-type' header charset value
  should be 'utf-8'.
  (@thekid)

## 1.8.1 / 2022-11-02

* Fixed importing from local directory not populating suggest index for
  all entries.
  (@thekid)

## 1.8.0 / 2022-11-02

* Merged PR #30: Include location names for autocompletion. Suggestions
  appear in the following order:
  1. Direct matches in the title (*as before*)
  2. Journeys with matches in the locations field
  3. Other content with matches in the locations field
  (@thekid)
* Merged PR #29: Read configuration from *config.ini* if available. See
  feature request story "Installation wizard" in issue #27
  (@thekid)

## 1.7.0 / 2022-10-30

* Merged PR #28: Toggle light and dark modes with button, see issue #9
  (@thekid)

## 1.6.1 / 2022-10-29

* Upgraded `xp-forge/frontend` library to version 4, see issue #19

## 1.6.0 / 2022-10-29

* Fixed issue #25: EXIF data on mobile - now displayed below lightbox
  (@thekid)
* Add lazy loading for all but the first 3 cards in *journeys* overview
  (@thekid)
* Use `xp-framework/networking` release version instead of development
  branch: https://github.com/xp-framework/networking/releases/tag/v10.4.0
  (@thekid)

## 1.5.0 / 2022-10-28

* Merged PR #23: Add robots.txt - be crawler-friendly, prevent 404s
  (@thekid)
* Merged PR #24: Replace search icon, add favicon from FontAwesome
  (@thekid)

## 1.4.0 / 2022-10-23

* Merged PR #21: Extract mapping script to src/main/js - @thekid

## 1.3.0 / 2022-10-22

* Merged PR #20: Implement search function using MongoDB Atlas Search
  (@thekid)
* Added small improvements to /feed and improved LightHouse score
  See issue #18
  (@thekid)

## 1.2.0 / 2022-10-08

* Changed home page to display *from* and *until* dates for journeys
  instead of just showing their start date
  (@thekid)
* Changed `alt` attributes to continue entry title and date. First part
  of feature suggested in #16
  (@thekid)

## 1.1.1 / 2022-10-05

* Fixed missing `alt` attributes for images. See #15 - @thekid

## 1.1.0 / 2022-10-04

* Merged PR #14: Show a map and list of all journeys - @thekid

## 1.0.0 / 2022-10-03

* Fix scroll links on Safari - `li::marker` doesn't work - @thekid
* Add links to scroll back to the top of the page to journeys - @thekid

## 0.9.0 / 2022-10-02

* Sort images by date and time originally taken. This fixes the ordering
  when using images from multiple cameras (e.g. DSLR and smart phone)
  and when image numbers wrap around (IMG_9999.jpg -> IMG_0001.jpg)
  (@thekid)

## 0.8.0 / 2022-10-02

* Merged PR #11: Show EXIF meta data along with images - @thekid

## 0.7.0 / 2022-10-01

* Merged PR #10: Synchronize images. The import tool now uses server
  information to synchronize images instead of relying on the local
  directory being in a consistent state.
  (@thekid)
* Fixed *Codec AVOption g (set the group of picture (GOP) size) specified 
  for input file* error
  (@thekid)

## 0.6.0 / 2022-09-30

* Merged PR #8: Video support. Dialog can now import .mov, .mp4 and .mpeg
  files, converting them to web-optimized H.264 video and automatically
  extracting poster images.
  (@thekid)

## 0.5.0 / 2022-09-25

* Merged PR #7: Add cover page displaying the newest 5 entries from the
  feed, and optionally a cover picture and text.
  (@thekid)

## 0.4.0 / 2022-09-24

* Merged PR #6: Refactor codebase to use a repository instead of directly
  querying the database
  (@thekid)

## 0.3.0 / 2022-09-24

* Merged PR #5: Add OpenGraph meta data - not perfect yet as in certain
  cases no preview images are displayed but a good start
  (@thekid)
* Merged PR #4: Embed maps showing all journey points using OpenStreetMap
  (@thekid)

## 0.2.0 / 2022-09-17

* Merged PR #3: Aggregate coordinates from Google Maps links - @thekid
* Merged PR #2: Do not lazy-load first item's images - @thekid

## 0.1.0 / 2022-09-13

* Merged PR #1: Add import command - @thekid
* Hello World! First release - @thekid
