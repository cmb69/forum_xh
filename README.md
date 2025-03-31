# Forum\_XH

Forum\_XH facilitates the embedding of simple forums on a CMSimple\_XH
site. The user management relies on
[Memberpages](https://github.com/cmsimple-xh/memberpages) or
[Register\_XH](https://github.com/cmb69/register_xh).

Posting requires to be logged in as member, though the forums can be
viewed publicly. This way no further provisions are necessary to avoid
spam, and posting does not require any additional input such as user
name, email address etc.

Forum\_XH features a markup editor allowing basic
[BBCode](https://en.wikipedia.org/wiki/BBCode) markup. All data are
stored in flat files, so Forum\_XH is not suitable for
large amounts of data. Furthermore common advanced forum features
such as user signatures, PMs, subscribing to topics etc. are not
available.

## Table of Contents

- [Requirements](#requirements)
- [Download](#download)
- [Installation](#installation)
- [Settings](#settings)
- [Usage](#usage)
  - [BBCode](#bbcode)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Credits](#credits)

## Requirements

Forum_XH is a plugin for [CMSimple_XH](https://www.cmsimple-xh.org/).
It requires CMSimple_XH ≥ 1.7.0, and PHP ≥ 7.1.0.
Forum_XH also requires [Plib_XH](https://github.com/cmb69/plib_xh) ≥ 1.6;
if that is not already installed (see `Settings` → `Info`),
get the [lastest release](https://github.com/cmb69/plib_xh/releases/latest),
and install it.

## Download

The [lastest release](https://github.com/cmb69/forum_xh/releases/latest)
is available for download on Github.

## Installation

The installation is done as with many other CMSimple\_XH plugins. See
the [CMSimple\_XH
wiki](https://wiki.cmsimple-xh.org/doku.php/installation) for further
details.

1. Backup the data on your server.
1. Unzip the distribution on your computer.
1. Upload the whole directory `forum/` to your server into
   the `plugins/` directory of CMSimple\_XH.
1. Set write permissions for the subdirectories `config/`, `css/`,
   and `languages/`.
1. Navigate to `Plugins` → `Forum` in the back-end to check if all
   requirements are fulfilled.

## Settings

The configuration of the plugin is done as with many other
CMSimple\_XH plugins in the back-end of the website.
Select `Plugins` → `Forum`.

You can change the default settings of Forum\_XH under `Config`.
Hints for the options will be displayed
when hovering over the help icon with your mouse.

Localization is done under `Language`.
You can translate the character strings to your own language
if there is no appropriate language file available,
or customize them according to your needs.

The look of Forum\_XH can be customized under `Stylesheet`.

## Usage

Inserting a forum on a page is done with the following plugin call:

    {{{forum('name-of-the-forum')}}}

You can have as many forums as you like, but at most one per CMSimple\_XH page.
The forums are distinguished by their name, which may contain lowercase
letters (`a`-`z`), digits (`0`-`9`) and hyphens (`-`) only.

After switching to view mode you will see the forum, and if it already
contains topics, you can navigate through them. Posting new comments
requires that you are logged in via
[Memberpages](https://github.com/cmsimple-xh/memberpages)
or [Register\_XH](https://github.com/cmb69/register_xh).
Then you can also edit and delete your own posts.
The CMSimple\_XH administrator can edit and delete the posts from all users,
but in order to post new comments,
the administrator has to log in additionally as member.
No additional admin functionality is provided in the back-end.

### BBCode

To allow users to markup their comments, basic BBCode is available.
The following elements are supported:

- `[b]bolded text[/b]`:
  **bolded text**
- `[i]italicized text[/i]`:
  *italicized text*
- `[u]underlined text[/u]`:
  <ins>underlined text</ins>
- `[s]strikethrough text[/s]`:
  ~~strikethrough text~~
- `[url]https://cmsimple-xh.org/[/url]`:
  <https://cmsimple-xh.org/>
- `[url=https://cmsimple-xh.org/]CMSimple\_XH[/url]`:
  [CMSimple\_XH](https://cmsimple-xh.org/)
- `[img]https://cmsimple-xh.org/userfiles/images/flags/en.gif[/img]`:
  ![](https://cmsimple-xh.org/userfiles/images/flags/en.gif)
- `[iframe]https://cmsimple-xh.org/userfiles/images/flags/en.gif[/iframe]`:
  ![](https://cmsimple-xh.org/userfiles/images/flags/en.gif)  
  *Iframes are used to embed external content, for instance, Youtube videos.*
- `[size=150]large text[/size]`:
  <span style="font-size:150%">large text</span>
- `[list][*]One [*]Two[/list]`:
  - One
  - Two
- `[list=1][*]One [*]Two[/list]`:
  1. One
  1. Two
- `[quote]quoted text[/quote]`:
  <blockquote>quoted text</blockquote>
- `[code]monospaced text[/code]`:
  `monospaced text`

Note that nesting of the same kind of elements is not allowed (for
instance, nested lists are not possible). The HTML created by the BBCode
converter is always valid and secure (i.e. no script injection is
possible). If any nesting of BBCode elements would result in invalid
HTML, the offending elements will simply be ignored.

The usage of the markup editor should be pretty much self explaining. Please
note that a simple preview is available via the rightmost button. If
JavaScript is not available in the browser of the user, the editor is not
available, but using BBCode in the textarea is still possible.

## Troubleshooting

Report bugs and ask for support either on
[Github](https://github.com/cmb69/forum_xh/issues)
or in the [CMSimple\_XH Forum](https://cmsimpleforum.com/).

## License

Forum\_XH is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Forum\_XH is distributed in the hope that it will be useful,
but *without any warranty*; without even the implied warranty of
*merchantibility* or *fitness for a particular purpose*. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Forum\_XH.  If not, see <https://www.gnu.org/licenses/>.

Copyright © Christoph M. Becker

Danish translation © Jens Maegard<br>
Russian translation © Lybomyr Kydray

## Credits

The plugin logo is designed by [Dezinerfolio](https://www.dezinerfolio.com/).
Many thanks for publishing this icon as freeware.

The emoticons were taken from the [LED icon pack](http://led24.de/).
Many thanks for publishing these icons under CC BY-SA.

Many thanks to the community at the
[CMSimple\_XH forum](https://www.cmsimpleforum.com/)
for tips, suggestions and testing.
Especially I want to thank *Traktorist* and *Old* for many good suggestions.
Also many thanks to *Ulrich* for reviving the plugin,
and *lck* for contributing iframe markup support.

And last but not least many thanks to
[Peter Harteg](https://www.harteg.dk/), the “father” of CMSimple,
and all developers of [CMSimple\_XH](https://www.cmsimple-xh.org/)
without whom this amazing CMS would not exist.
