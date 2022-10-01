Dialog change log
=================

## ?.?.? / ????-??-??

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
