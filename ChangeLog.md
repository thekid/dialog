Dialog change log
=================

## ?.?.? / ????-??-??

## 3.0.0 / ????-??-??

* Merged PR #72: Show lens model and creation date from EXIF and XMP
  segments. **Heads up:** This requires to run a migration script, see
  the pull request!
  (@thekid)
* Enabled keyboard navigation with pos1, end and left and right arrow
  keys, and swiping left and right inside lightbox
  (@thekid)
* Merged PR #71: Aggregate weather for entries when importing. This uses
  the free https://open-meteo.com/en/docs/historical-weather-api API
  (@thekid)
* Merged PR #70: Speed up importing from local directory. The `import`
  tool was more or less completely rewritten for this purpose. As a side
  effect, it's way more extensible
  (@thekid)
* Fixed issue #69: ffpmeg warnings when converting videos - @thekid
* Implemented support for conditional requests in Atom feed using the
  `If-Modified-Since` header.
  (@thekid) 
* Merged PR #68: Show preview images instead of marker icons on map
  (@thekid)
* Merged PR #67: Switch journeys to display entries oldest - newest
  (@thekid)
* Upgraded Docker image to PHP 8.4 following its release on 2024-11-21
  https://www.php.net/releases/8.4/en.php
  (@thekid)

## 2.6.0 / 2024-12-07

* Assigned a high *fetchpriority* to the cover image to further improve
  home page performance. See #65
  (@thekid)
* Merged PR #66: Add an Atom feed at `/feed/atom`, showing the 20 newest
  entries, each with one preview image. Requested in #63.
  (@kiesel, @thekid)
* Changed maps to not interrupt page scrolling as requested in #64
  (@kiesel, @thekid)
* Changed image import to rounds focal lengths, which are potentially
  expressed as a fraction
  (@thekid)
* Merged PR #62: Extract OpenLayers mapping JS & CSS into separate
  bundle, reducing the amount of JS to be loaded for the home page by
  more than 800 kilobytes
  (@thekid)

## 2.5.0 / 2024-09-29

* Merged PR #57: Use CSS nesting, see https://caniuse.com/css-nesting
  (@thekid)

## 2.4.0 / 2024-09-15

* Merged PR #61: Refactor to make use of asymmetric visibility
  (@thekid)
* Updated the *OpenLayers* JavaScript library to version 10.1, see
  https://github.com/openlayers/openlayers/releases
  (@thekid)

## 2.3.0 / 2024-05-20

* Preload the cover image, increasing the LightHouse score on the
  home page to 99. See #18
  (@thekid)

## 2.2.0 / 2024-05-16

* Updated the *OpenLayers* JavaScript library to version 9.2, see
  https://github.com/openlayers/openlayers/releases
  (@thekid)

## 2.1.2 / 2024-05-12

* Fixed the `video` elements in having an unwanted bottom "margin".
  Use *display: block* to prevent this, just like with images.
  (@thekid)

## 2.1.1 / 2024-05-06

* Fixed [descenders](https://en.wikipedia.org/wiki/Descender) being
  cut off on cards
  (@thekid)

## 2.1.0 / 2024-04-28

* Added *LIVE* display inside feed, fixed query problem when images
  are not set yet
  (@thekid)
* Upgraded `xp-framework/imaging` and `xp-forge/web-auth` libraries,
  removing dependency on the XML library
  (@thekid)
* Upgraded `xp-framework/command` library, being able to adjust the
  argument methods' prefixes to the much nicer *use*.
  (@thekid)

## 2.0.0 / 2024-03-24

* Changed main font from *Overpass* to *Barlow* - its rounded edges
  fit better with the rest of the design
  (@thekid)
* Upgraded XP Compiler & Core - dropping support for PHP 7.0 - 7.3
  (@thekid)
* Merged PR #59: Use `Collection::modify()` instead of invoking
  "findAndModify"
  (@thekid)

## 1.18.0 / 2024-03-02

* Upgraded `xp-forge/markdown` and `xp-forge/web-auth` libraries
  (@thekid)
* Merged PR #58: Balance layout for odd number of preview images
  (@thekid)

## 1.17.0 / 2024-02-25

* Merged PR #56: Use semantic HTML `<search>` element, see here:
  https://developer.mozilla.org/en-US/docs/Web/HTML/Element/search
  (@thekid)
* Upgraded `xp-forge/web`, `xp-forge/frontend` and `xp-forge/yaml`
  libraries
  (@thekid)
* Upgraded to PHP 8.3 and XP 8.8 - @thekid

## 1.16.0 / 2023-09-16

* Merged PR #55: Extract meta data from MP4 / MOV atoms, making it
  possible to mix videos and images in albums and have them sorted
  correctly by date and time.
  (@thekid)

## 1.15.2 / 2023-09-09

* Fixed support for unnamed places on Google Maps - @thekid

## 1.15.1 / 2023-08-19

* Fixed images being re-uploaded every time - @thekid

## 1.15.0 / 2023-04-30

* Merged PR #54: Instead of manually aggregating children, use MongoDB 5.2+
  features, implementing #52 now that Atlas free tier is finally at 6.0
  (@thekid)
* Merged PR #53: Migrate to new testing library - @thekid

## 1.14.0 / 2023-01-15

* Merged PR #51: Display newest children as cards if no images are given
  (@thekid)
* Merged PR #50: Display "LIVE" on card if journey is current - @thekid
* Merged PR #49: Select preview image from latest child element if none
  available instead of leaving it empty. This will update the preview
  image every time new content is added.
  (@thekid)
* Fixed card display when no preview images are available - @thekid

## 1.13.0 / 2022-12-10

* Merged PR #47: Serve favicon, some bots seem to request them regardless
  of meta property
  (@thekid)

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
