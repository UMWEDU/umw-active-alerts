# UMW Active Alerts #
**Contributors:** [cgrymala](https://profiles.wordpress.org/cgrymala)

**Donate link:** http://giving.umw.edu/

**Tags:** advisories, alerts, emergency, notification

**Requires at least:** 3.9.1

**Tested up to:** 4.1.1

**Stable tag:** 0.6

**License:** GPLv2 or later

**License URI:** http://www.gnu.org/licenses/gpl-2.0.html


Implements the emergency and non-emergency alerts and advisories displayed on the [UMW website](http://www.umw.edu/).

## Description ##

This plugin introduces a new post type for advisories, as well as a handful of new terms to help categorize those advisories.

Individual site administrators can create new local advisories that will appear throughout their site. These are known as "Department, Division and Program Alerts".

In addition, administrators and editors of the root Advisories site can create two different types of alerts:

* Current University-wide Non-Emergency Alerts
* Current University-wide Emergency Notifications

The local advisories will appear in the advisory archive on the main Advisories site, and will also appear throughout the site that initially created the advisory.

The university-wide emergency alerts will appear throughout the entire installation.

The university-wide non-emergency alerts will appear on the root home page and within the main Advisories site.

## Frequently Asked Questions ##

### How do I create a local advisory? ###

1. Login to your site
1. Create a new "Advisory"
1. Enter a title for the advisory; this should be explanatory and unique, as it will be the headline that is displayed across your individual site.
1. Choose the appropriate expiration date & time for the advisory; the default expiration is 24 hours after you create it.

### How do I create a university-wide advisory? ###

Only administrators and editors of the main Advisories site can create university-wide alerts.

On the main Advisories site, the "Post" content type is used for the advisories. In order to create a new university-wide alert, simply login to the Advisories site and create a new "Post".

Once you enter the title for the advisory (again, be explanatory and unique, as this is the main headline that will be displayed everywhere), enter some content for the advisory.

After you enter the advisory heading and content, choose the appropriate category. You should choose either "Current University-wide Emergency Notifications" or "Current University-wide Non-Emergency Alerts". If you don't choose a category, the advisory will automatically be assigned to the non-emergency category.

Choose an appropriate expiration date/time for the alert. The default expiration is 24 hours after the advisory is created.

## Changelog ##

### 0.6 ###
* Fix XSS vulnerability in jQuery code
* Move script enqueues to appropriate action
* Make style changes for use of SlideDeck slideshow plugin

### 0.5 ###
* Implements local alerts, separate from the university-wide alerts
* Improved administrative control over advisories
* Style tweaks
* Code modularization and commenting

### 0.4 ###
* Fully implements separate emergency/non-emergency notifications

### 0.3 ###
* Added widget and shortcode
* Modified styles
* Began implementing cross-site widget

### 0.2 ###
* Began implementing separate emergency/non-emergency alerts

### 0.1 ###
* Initial version
