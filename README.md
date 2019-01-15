# tinydb
A community driven database for commonly used cia files. Easially accessible via QR-Codes. 

## Features:
- Automated scanning for new releases on github
   - As a homebrew developer you will not have to worry about keeping your tinydb entry up to date
   - As a user, this ensures up to date software 100% of the time
- QR Codes support older FBI versions/All available QR capable cia installers
- Categorization to help you find the applications you want

## Feature Roadmap:
- Standalone 3ds application to browse tinydb and keep your homebrew collection up to date
- Moderation GUI for trusted "helpers" to manage tinydb entries
- 3dsx support
- c library that can be integrated into Homebrew Applications to facilitate automatic updates
- Blacklist/Filtering to minimize repetitive moderation work

## Public API Documentation: 

This is a very crud explaination of the API, it is going to grow as more features get added. Detailed Documentation will follow.

Available Endpoints:

- "/apps" : As one might expect, this returns a list of all apps including all releases. 
- "/apps?app_id=1" : Above endpoint can also be used with an app_id parameter to only get releases for 1 specific application. For example fbi has id 1. This filter can be used for automatic updates.
- "/apps?category_id=3" : By specifying the category_id parameter you only get apps and releases that are mapped to one specific category (NOTE: Can not be used in combination with the app_id, please only specify the app_id in that case)
- "/categories" : This endpoint returns a list of all categories with an app count for each category.

API Base URL: https://tinydb.eiphax.tech/api
