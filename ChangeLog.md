Dialog change log
=================

## ?.?.? / ????-??-??

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
