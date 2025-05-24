# Auto Archive.org Submitter - WordPress Plugin

![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/auto-archive-submitter) ![WordPress Plugin Active Installs](https://img.shields.io/wordpress/plugin/installs/auto-archive-submitter) ![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/auto-archive-submitter)

Automatically submits your WordPress site pages to Archive.org (Wayback Machine) when visitors access them, preserving your content for future reference.

## Features

- **Automatic Submission**: Every page visited by users gets submitted to Archive.org
- **Smart Throttling**: Prevents duplicate submissions with configurable intervals (default: 24 hours)
- **URL Exclusion**: Option to exclude specific URLs from being archived
- **Activity Logging**: Detailed logs of all submission attempts
- **Non-Blocking**: Submits in background without affecting page load speed
- **Easy Configuration**: Simple settings page in WordPress admin

## Installation

1. Download the plugin ZIP file or clone this repository
2. Upload the `auto-archive-submitter` folder to your `/wp-content/plugins/` directory
3. Log in to your WordPress admin dashboard
4. Navigate to "Plugins" and activate "Auto Archive.org Submitter"
5. Configure the plugin settings under "Settings" > "Archive Submitter"

## Configuration Options

- **Enable Plugin**: Toggle the plugin on/off
- **Enable Logging**: Record submission activity to a log file
- **Submission Interval**: Minimum time between submissions for the same URL (in seconds)
- **Excluded URLs**: List of URLs to exclude from archiving (one per line)

## How It Works

1. When a visitor accesses any page on your WordPress site
2. The plugin checks if the URL should be submitted (not excluded, not recently submitted)
3. If eligible, it sends a non-blocking request to Archive.org's save page service
4. Results are logged for future reference

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WordPress site with public internet access

## Frequently Asked Questions

**Does this slow down my website?**  
No, the plugin uses non-blocking requests that don't affect page load time.

**How often will my pages be archived?**  
By default, each unique URL will be submitted once every 24 hours. You can adjust this interval in settings.

**Can I exclude certain pages?**  
Yes, the plugin includes an exclusion list where you can specify URLs not to archive.

**Where can I view the archived versions?**  
Visit `https://web.archive.org/web/*/yourdomain.com` to see all archived versions.

## Changelog

**1.0**  
- Initial release with core functionality
- Basic configuration options
- Activity logging system

## Support

For support or feature requests, please [open an issue on GitHub](https://github.com/yourusername/auto-archive-submitter/issues)