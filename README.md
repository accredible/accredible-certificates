![Accredible Logo](https://s3.amazonaws.com/accredible-cdn/accredible_logo_sm.png)

# Accredible Wordpress Certificates & Open Badges Plugin

## Overview

The Accredible platform enables organizations to create, manage and distribute digital credentials as digital
certificates or open badges.

An example digital certificate and badge can be viewed here: https://www.credential.net/10000005

This plugin enables you to issue dynamic, digital certificates or open badges on your Wordpress instance.

The plugin also allows you to display certificates and badges to your Wordpress users.

A video walkthrough of the plugin can be viewed here: https://youtu.be/s5krPN6GvTY

## Example Output

![Example Digital Certificate](https://s3.amazonaws.com/accredible-cdn/example-digital-certificate.png)

![Example Open Badge](https://s3.amazonaws.com/accredible-cdn/example-digital-badge.png)

## Compatability

Tested on Wordpress 3+.

---

## Installation

1. Visit https://accredible.com to obtain an API key
2. Install the plugin
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to the plugin settings and input your API key
5. Add the widget if desired. It will show a list of certificates or badges for a currently signed in user.
6. Add the shortcode below to a page or post if desired. It will show certificates and badges for a currently signed in user.

```
[accredible_credential image="true" limit="10" style="true"] 
```

### Manually creating certificates

1. Go to the 'Certificates & Badges' page in the Wordpress admin menu.
2. On the list of your users, select which students you would like to issue certificates to and then select a group.
3. Click 'Create Credentials'

![Certificates Admin](https://s3.amazonaws.com/accredible-moodle-instructions/wordpress/certificates-admin.png)

## Frequently Asked Questions

#### How do I get an API key?

Visit https://www.accredible.com to obtain a free API key.

#### How can I show certificates or badges to my users?

You can use the widget or shortcode to display badges or certificates that belong to the current Wordpress user.

The shortcode is:
```
[accredible_credential]
```
but it accepts a number of options:
```
[accredible_credential image="true" limit="10" style="true"]
```

#### Can you add support for another Wordpress LMS or theme?

Sure, just post an issue and we'll get to work: https://github.com/accredible/accredible-certificates/issues

---

## Development Information

### Development setup

- ⚠ Be careful not to **UNINSTALL** the plugin in this docker environment since **WordPress completely removes your local plugin directory** on uninstallation including `.git` so you will lose all your work in the directory. (In contrast, activation and deactivation of the plugin do not trigger any dangerous operations.)

#### Prerequisites

- [Docker](https://www.docker.com/)

#### Step 1: Run Docker containers

Build and run docker containers with `docker-compose`.

```
docker-compose up -d
```

### WordPress

After the setup, WordPress should be running on port `8000`  of your Docker Host. Open it in a web browser and complete the WordPress installation.
