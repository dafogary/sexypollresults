# Sexy Poll Results Plugin for Joomla

**Version:** 0.0.1 Alpha  
**Author:** Gary Foster – DAFO Creative Ltd/LLC  
**Contributor:** JDev GPT by Nijssen Media  
**Compatibility:** Joomla 4.x / 5.x  
**License:** GNU Affero General Public License v3 (AGPL-3.0)  

---

## Overview

**Sexy Poll Results** is a Joomla content plugin that displays voting results from the **Sexy Polling Reloaded** component.  
It lets you embed poll results directly into Joomla articles using shortcodes such as:

```text
{sexyresults poll=3 month=2025-01}
```

The plugin supports:

- Filtering results by poll ID and month
- Multiple shortcodes per article
- Two visual styles:
	- Bar Chart (Bootstrap progress bars with gradient)
	- Pie Chart (Chart.js with optional slice labels)
- Fully configurable colors, chart size, and label visibility

# Installation

Download the plugin ZIP package (plg_content_sexypollresults.zip).

In Joomla Administrator, go to:
System → Install → Extensions → Upload Package File

Upload and install the ZIP.

Go to System → Manage → Plugins, find
“Content - Sexy Poll Results”, and enable it.

(Optional) Open the plugin settings to configure default chart type, colors, size, and label display.

# Plugin Parameters

| Setting                       | Type   | Description                                                                    |
| ----------------------------- | ------ | ------------------------------------------------------------------------------ |
| **Default Chart Type**        | List   | Default display style when shortcode doesn’t specify `type=` (`bar` or `pie`). |
| **Gradient Start Color**      | Color  | The left/start color for gradient bar charts.                                  |
| **Gradient End Color**        | Color  | The right/end color for gradient bar charts.                                   |
| **Pie Chart Size (px)**       | Number | Width and height of the pie chart in pixels. Default: `320`.                   |
| **Show Labels on Pie Slices** | Toggle | Display the answer and percentage overlayed on each pie slice. Default: `Yes`. |

# Usage

Insert shortcodes directly into any article’s content.

## Basic Example

```text
{sexyresults poll=3 month=2025-01}
```

Displays results for Poll ID 3, limited to votes from January 2025.

## Specify Chart Type

```text
{sexyresults poll=3 month=2025-01 type=pie}
{sexyresults poll=4 month=2025-02 type=bar}
```

* bar → Bootstrap gradient bars
* pie → Chart.js pie chart (requires CDN access)

## Multiple Shortcodes Per Article

```text
<h3>January Results</h3>
{sexyresults poll=3 month=2025-01 type=pie}

<h3>February Results</h3>
{sexyresults poll=3 month=2025-02 type=bar}
```

# Output Examples
## Bar Chart Example

Gradient progress bars using your configured start/end colors.

Shows both vote count and percentage per answer.

Clean Bootstrap 5 layout.

Diagram to do.

## Pie Chart Example

Built using Chart.js (loaded from CDN).

Adjustable chart size via plugin settings.

Optional overlay labels (answer + percentage).

Diagram to do.

# Requirements

Joomla 5.x

Sexy Polling Reloaded component installed

Database tables:

* #__sexy_polls
* #__sexy_answers
* #__sexy_votes

# Troubleshooting

**Error:**

```text
Save failed with the following error: Table 'xxxxxx.jos_sexy_poll_votes' doesn't exist

```
**Fix:** Check your database table names — the plugin assumes:

```text
#__sexy_polls
#__sexy_answers
#__sexy_votes
```

If your installation uses a different naming convention, adjust the table names in:


```text
plugins/content/sexypollresults/helper.php

```
within the SQL query section.

# Notes

* Chart.js and the datalabels plugin load automatically only when required.
* All output is HTML5-valid and uses Joomla’s built-in Bootstrap 5 styling.
* Results are grouped by poll answer (a.id) and filtered by vote month using YEAR(v.date) and MONTH(v.date).

# Credits

Developed by Gary Foster – DAFO Creative Ltd/LLC with the help of AI

# License

Released under the GNU Affero General Public License v3.

You are free to use, modify, and redistribute this plugin, provided all derivative works remain licensed under the AGPL and include attribution.
